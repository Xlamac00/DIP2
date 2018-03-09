
$(document).ready(function() {
    var canvas = document.getElementById("animatedChart");
    var changeActive = false;
    var commentActive = false;
    var changeBar = false;
    var changeOld = [];

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });

    canvas.onmousemove = function (evt) { chartMouseMoveEvent(evt);};
    canvas.onmousedown = function (evt) { chartMouseDownEvent(evt);};
    canvas.onmouseup = function (evt) { chartMouseUpEvent();};

    /** *****************************************************************************************************
     * ************************************* RESIZABLE CHART FUNCTIONS ************************************ *
     ***************************************************************************************************** **/

    // Redraws the bar depending on the cursor position
    function chartMouseMoveEvent(evt) {
        if (changeActive) { //the mouse was pressed over a graph bar
            var base = (getChartMetaData().data[changeBar]._model.base);
            var perc = (base - evt.offsetY) / (base / 100);
            if (perc <= 1 || perc > 98) { // redraw and stop
                var oldPerc = (base - changeOld['y']) / (base / 100);
                if(oldPerc < 1.5 && perc < 1.5) // old value was also low -> unintended behaviour
                    return;
                console.log("Ending actiove mode");
                perc = perc <= 1 ? 1 : 100;
                changeActive = false;
                ajaxUpdateGraph(perc);
            }
            else { // redraw the graph
                chart.data.datasets[0].data[changeBar] = perc;
                updateGraphValue(false, perc);
            }
        }
    }
    // Checks if the mouse was pressed over a graph bar and if so,
    // switches the bar into active mode and saves the coordinates
    // of original bar to draw the old line
    function chartMouseDownEvent(evt) {
        var bar = wasClickedOnBar(evt.offsetX, evt.offsetY);
        if (bar !== false) {
            var coords = getChartMetaData().data[bar]._model;
            changeActive = true;
            changeBar = bar;
            changeOld = {
                "x1": (coords.x - coords.width / 2),
                "x2": (coords.x + coords.width / 2),
                "y": (coords.y)
            };
        }
        else {
            changeActive = false;
            changeBar = false;
        }
    }
    // Ends the active mode
    function chartMouseUpEvent() {
        if (changeActive) {
            changeActive = false;
            var newValue = chart.data.datasets[0].data[changeBar];
            ajaxUpdateGraph(newValue);
        }
    }

    // Checks the coordinates of mouse click with dimensions of bars in graph
    function wasClickedOnBar(x, y) {
        for (var i = 0; i < getChartMetaData().data.length; i++) {
            var bar = getChartMetaData().data[i]._model;
            if (x > (bar.x - bar.width / 2) && x < (bar.x + bar.width / 2) &&
                y + 5 > bar.y && y - 5 < bar.base) {
                return i;
            }
        }
        return false;
    }

    /** Gets the meta data from the chart.
     * Due to the changes to canvas when adding new gauges, the index of the _meta data in canvas
     * changes (plus one for each new gauge). Don't know how to change it, so this is the best
     * way to get the _meta data from the right index.
     * @returns {*}
     */
    function getChartMetaData() {
        var meta = chart.data.datasets[0]._meta;
        for(var i = 0; i < 5; i++) {
            if (typeof meta[i] !== 'undefined')
                return chart.data.datasets[0]._meta[i];
        }
    }

    function replaceChart(data) {
        chart.destroy();
        $('#animatedChart').replaceWith('<canvas id="animatedChart" height="1" width="2"></canvas>');
        var ctx = $('#animatedChart').get(0).getContext("2d");
        chart = new Chart(ctx, {
            type: 'bar',
            issueId: chart.config.issueId,
            data: {
                labels: data.labels.slice(0, -1).replace(/'/g,'').split(',') ,
                datasets: [{ data: data.values.slice(0, -1).replace(/'/g,'').split(','),
                    backgroundColor: data.colors.slice(0, -1).replace(/'/g,'').split(','),
                    borderColor: data.colors.slice(0, -1).replace(/'/g,'').split(','),
                    borderWidth: 1  }]
            },
            options: options
        });

        // Item was replaced, so all the events must be registered again
        var canvas = document.getElementById("animatedChart");
        canvas.onmousemove = function (evt) { chartMouseMoveEvent(evt);};
        canvas.onmousedown = function (evt) { chartMouseDownEvent(evt);};
        canvas.onmouseup = function (evt) { chartMouseUpEvent();};
    }

    /** Changes the value of the graph.
     *
     * @param stopActiveMode (boolean) - stop reacting to mouse move and redrawing
     * @param newValue (int) - new percentual value for the graph
     */
    function updateGraphValue(stopActiveMode, newValue) {
        chart.data.datasets[0].data[changeBar] = newValue;
        chart.update(); // updates the graph

        if(stopActiveMode === true) { // stop reacting to mouse move
            changeActive = false;
            changeBar = false;
        }
    }

    Chart.pluginService.register({
        // Adds a straight line to the graph to show his previous value
        // If the graph is in active mode, draws the line with original bar value
        afterDraw: function (chart) {
            if (changeActive) {
                chart.ctx.beginPath();
                chart.ctx.setLineDash([0]);
                chart.ctx.moveTo(changeOld['x1'], changeOld['y']);
                chart.ctx.strokeStyle = '#999';
                chart.ctx.lineTo(changeOld['x2'], changeOld['y']);
                chart.ctx.stroke();
            }
            else if(commentActive) {
                chart.ctx.beginPath();
                chart.ctx.setLineDash([5]);
                chart.ctx.moveTo(changeOld['x1'], changeOld['y']);
                chart.ctx.strokeStyle = '#999';
                chart.ctx.lineTo(changeOld['x2'], changeOld['y']);
                chart.ctx.stroke();
            }
        },
        // Adds labels to the graph body
        // http://www.chartjs.org/samples/latest/advanced/data-labelling.html
        // afterDatasetsDraw: function(chart) {
        //     var ctx = chart.ctx;
        //
        //     chart.data.datasets.forEach(function(dataset, i) {
        //         var meta = chart.getDatasetMeta(i);
        //         if (!meta.hidden) {
        //             meta.data.forEach(function(element, index) {
        //                 // Draw the text in black, with the specified font
        //                 ctx.fillStyle = 'rgb(0, 0, 0)';
        //
        //                 var fontSize = 16;
        //                 var fontStyle = 'normal';
        //                 var fontFamily = 'Helvetica Neue';
        //                 ctx.font = Chart.helpers.fontString(fontSize, fontStyle, fontFamily);
        //
        //                 // Just naively convert to string for now
        //                 var labelValue = dataset.data[index].toString();
        //                 var labelText = meta.data[index]._model.label;
        //
        //                 // Make sure alignment settings are correct
        //                 ctx.textAlign = 'center';
        //                 ctx.textBaseline = 'middle';
        //
        //                 var padding = labelValue > 60 ? -45 : 10;
        //                 var position = element.tooltipPosition();
        //                 ctx.fillText(labelText, position.x, position.y - (fontSize / 2) - padding);
        //             });
        //         }
        //     });
        // }
    });

// Key shortcuts to commit or discard changes
    document.onkeypress = function (e) {
        if (commentActive) { // any new change is now active to commit
            e = e || window.event;
            if (e.keyCode === 13) { // Enter
                if (!$('#gaugeCommentText').is(':focus')) { // user is not writing
                    ajaxCommentChange();      // save the gauge change to DB
                }
            }
            if (e.keyCode === 27) { // Escape
                ajaxDiscardChange();        // discard latest change
            }
        }
    };

    /** *****************************************************************************************************
     * ****************************************** BUTTONS AND UI ****************************************** *
     ***************************************************************************************************** **/

    $('#gaugeCommentShowAllBtn').click(toggleAllComments);
    function toggleAllComments() {
        // get all comments in div gaugeComments
        var commentsCount = $("#gaugeComments div.media").length;
        var showBtn = document.getElementById('gaugeCommentShowAllBtn');
        if(showBtn.style.display === 'none' && commentsCount > 6) { // make the button visible
            for(var i = 0; i < commentsCount; i++) {
                if(i > 6) // hide any more then first six
                    ($("#gaugeComments div.media")[i]).style.display = 'none';
            }
            showBtn.style.display = 'block';
        }
        else {
            for(var i = 0; i < commentsCount; i++) {
                ($("#gaugeComments div.media")[i]).style.display = 'flex';
            }
            showBtn.style.display = 'none';
        }
    }
    toggleAllComments(); // call after the page loads

    $('#gaugeAddNewBtn').click(showAddNewGauge);
    $('#gaugeAddNewCloseBtn').click(showAddNewGauge);
    function showAddNewGauge() {
        var commentSection = document.getElementById('gaugeCommentSection');
        var newSection = document.getElementById('gaugeNewSection');
        var addNewBtn = document.getElementById('gaugeAddNewBtn');
        console.log('click: '+commentSection.style.display);
        if (commentSection.style.display === 'block') {
            commentSection.style.display = 'none';
            newSection.style.display = 'block';
            addNewBtn.blur();
        }
        else {
            commentSection.style.display = 'block';
            newSection.style.display = 'none';
            addNewBtn.blur();
        }
    }

    /** *****************************************************************************************************
     * ******************************************** AJAX CALLS ******************************************** *
     ***************************************************************************************************** **/

    $('#gaugeAddNewSaveBtn').click(saveNewGauge);
    function saveNewGauge() {
        var name = $('#gaugeAddNewName').val();
        var issue = chart.config.issueId;
        if(name.length > 0) {
            document.getElementById('gaugeAddNewName').className = 'form-control';
            $.ajax({
                url:path_ajax_newGauge,
                type: "POST",
                dataType: "json",
                data: {
                    "issueId": issue,
                    "name": name,
                    "color": $('input[name=radio]:checked', '#gaugeAddNewForm').val()
                },
                async: true,
                success: function (data) {
                    replaceChart(data);
                    $("#gaugeCommentSection").css('display', 'block');
                    $("#gaugeNewSection").css('display', 'none');
                    $("#gaugeAddNewName").val('');
                }
            });
        }
        else // field name is empty
            document.getElementById('gaugeAddNewName').className = 'form-control is-invalid';
    }

    $('#gaugeChangeResetBtn').click(ajaxDiscardChange); // set function call from btn
    /** Sends ajax request to discard last gauge change from db.
     *
     */
    function ajaxDiscardChange() {
        $.ajax({
            url: path_ajax_graphDiscard,
            type: "POST",
            dataType: "json",
            data: {
                "issueId": chart.config.issueId
            },
            async: true,
            success: function (data) {
                changeBar = data.position;
                updateGraphValue(true, data.newValue); // redraw graph value and stop
                $("#gaugeChangeCommit").css('display', 'none');
                commentActive = false;
                chart.ctx.setLineDash([0]);
                chart.update();
            }
        });
    }

    $('#gaugeChangeConfirmBtn').click(ajaxCommentChange); // set function call from btn
    function ajaxCommentChange() {
        $.ajax({
            url: path_ajax_graphComment,
            type: "POST",
            dataType: "json",
            data: {
                "issueId": chart.config.issueId,
                "text": $('#gaugeCommentText').val()
            },
            async: true,
            success: function (data) {
                $('#gaugeCommentText').val('');
                $("#gaugeChangeCommit").css('display', 'none');
                commentActive = false;
                chart.ctx.setLineDash([0]);
                chart.update();
                var oldHtml = document.getElementById('gaugeComments').innerHTML;
                document.getElementById('gaugeComments').innerHTML = data + oldHtml;
            }
        });
    }

    /** Sends ajax request to save new graph value to db.
     *
     */
    function ajaxUpdateGraph(newValue) {
        $.ajax({
            url: path_ajax_graphChange,
            type: "POST",
            dataType: "json",
            data: {
                "issueId": chart.config.issueId,
                "gaugeNumber": changeBar,
                "gaugeValue": newValue
            },
            async: true,
            success: function (data) {
                commentActive = true;
                updateGraphValue(true, data.newValue); // redraw graph value and stop
                $("#gaugeCommentHeadline").css('color', data.color);
                document.getElementById('gaugeCommentHeadline').innerHTML = data.oldValue + "% -> " + data.newValue + "%";
                $("#gaugeCommentSection").css('display', 'block');
                $("#gaugeNewSection").css('display', 'none');
                $("#gaugeChangeCommit").css('display', 'flex');
            }
        });
    }
});

