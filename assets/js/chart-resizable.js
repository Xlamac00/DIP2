
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
    canvas.onmouseup = function () { chartMouseUpEvent();};

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
        }
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
        else if(previousSection.length > 0) { // there is any section to hide
            if (e.keyCode === 27) { // Escape
                hideCurrentSection();
            }
        }
    };

    /** *****************************************************************************************************
     * ****************************************** BUTTONS AND UI ****************************************** *
     ***************************************************************************************************** **/
    var previousSection = []; // history of shown sections

    /** Shows all comments instead of only the first 6.
     * (If there are more then 6). Displays the button to trigger this function.
     */
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


    /** Closes the currently visible section and shows the previous one.
     * @see hideAllSections()
     */
    $('.gaugeCloseBtn').click(hideCurrentSection);
    function hideCurrentSection() {
        var previous = previousSection.pop();
        hideAllSections(false); // dont push the previous section into the queue!
        var section = $("#gaugeSections div.section");
        if(typeof section[previous] === 'undefined')
            previous = 0;
        section[previous].style.display = 'block'; // display the previous one
    }

    /** Hides all sections and remembers the last one visible.
     * The last visible section is pushed into the queue to remember the history.
     *
     * @param push (implicit true) - if true, remembers the last section.
     */
    function hideAllSections(push) {
        push = typeof push !== 'undefined' ? push : true;
        // make all other sections invisible
        var sections = $("#gaugeSections div.section");
        for(var i = 0; i < sections.length; i++) {
            if((sections[i]).style.display === 'block' && push === true) {
                var top = previousSection.pop();
                if(top !== 'undefined')
                    previousSection.push(top);
                if(top !== i) // kontrola, ze nevkladam 2x to stejne navrch
                    previousSection.push(i);
            }
            (sections[i]).style.display = 'none';
        }
    }

    /** Displays section with option to add new gauge.
     * If the section is already displayed, it hides it instead.
     */
    $('#gaugeAddNewBtn').click(showAddNewGauge);
    function showAddNewGauge() {
        var newSection = document.getElementById('gaugeNewSection');
        var addNewBtn = document.getElementById('gaugeAddNewBtn');
        if (newSection.style.display === 'block') { // section is already visible, hide it
            hideCurrentSection();
            addNewBtn.blur(); // hide the button tooltip
        }
        else { // display the section
            hideAllSections();
            document.getElementById('gaugeNewSection').style.display = 'block';
            addNewBtn.blur();
        }
    }

    /** Displays section with gauge edit options
     */
    $('#dropdownGaugeBtn').click(showEditGauge);
    function showEditGauge() {
        hideAllSections();
        ajaxGetGaugesInfo();
    }

    /** Displays section with issue edit options
     */
    $('#dropdownIssueBtn').click(showEditIssue);
    function showEditIssue() {
        hideAllSections();
        document.getElementById('gaugeEditIssueSection').style.display = 'block'; // make issue edit section visible
    }

    $('#questionNo').click(hideCurrentSection);
    $('#questionYes').click(ajaxSendQuestion);
    function showGaugeDeleteDialog() {
        hideAllSections();
        $('.gaugeDelete').blur();
        document.getElementById('questionText').innerHTML = 'delete this gauge';
        document.getElementById('questionCall').value = 'path_ajax_gaugeDelete';
        document.getElementById('questionValue1').value = this.name;
        document.getElementById('questionValue2').value = chart.config.issueId;
        document.getElementById('questionSection').style.display = 'block';
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
                    hideAllSections();
                    $("#gaugeCommentSection").css('display', 'block');
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
                hideAllSections();
                $("#gaugeCommentSection").css('display', 'block');
                $("#gaugeChangeCommit").css('display', 'flex');
            }
        });
    }

    function ajaxGetGaugesInfo() {
        $.ajax({
            url: path_ajax_gaugesInfo,
            type: "POST",
            dataType: "json",
            data: {
                "issueId": chart.config.issueId
            },
            async: true,
            success: function (data) {
                __showEditGaugeSection(data);
            }
        });
    }

    function __showEditGaugeSection(template) {
        var section = document.getElementById('gaugeEditGaugeSection');
        section.style.display = 'block'; // make gauge edit section visible
        section.innerHTML = template;
        $('#editGaugeCloseBtn').click(hideCurrentSection); //bind action to close btn
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        });
        $('.gaugeEdit').click(ajaxGetOneGaugeInfo); // bind action to open gauge edit dialog
        $('.gaugeDelete').click(showGaugeDeleteDialog); // bind action to open gauge edit dialog
    }

    function ajaxUpdateGauge() {
        $.ajax({
            url: path_ajax_gaugeUpdate,
            type: "POST",
            dataType: "json",
            data: {
                "issueId": chart.config.issueId,
                "gaugeId": $("#gaugeEditOneSection #gaugeUpdateId").val(),
                "name": $('#gaugeEditOneSection #gaugeAddNewName').val(),
                "color": $('input[name=radio]:checked', '#gaugeEditOneSection #gaugeAddNewForm').val()
            },
            async: true,
            success: function (data) {
                replaceChart(data);
                hideAllSections(false);
                __showEditGaugeSection(data.tab);
            }
        });
    }

    function ajaxGetOneGaugeInfo() {
        $.ajax({
            url: path_ajax_oneGaugeInfo,
            type: "POST",
            dataType: "json",
            data: {
                "gaugeId": this.name
            },
            async: true,
            success: function (data) {
                hideAllSections();
                var section = document.getElementById('gaugeEditOneSection');
                section.style.display = 'block'; // make gauge edit section visible
                section.innerHTML = data;
                $('.gaugeEdit').blur();
                $('.gaugeCloseBtn').click(hideCurrentSection); //bind action to close btn
                $('#gaugeEditOneSection #gaugeAddNewSaveBtn').click(ajaxUpdateGauge); //bind action to close btn
            }
        });
    }

    function ajaxSendQuestion() {
        $.ajax({
            url: window[document.getElementById('questionCall').value], // this value has to be set in question
            type: "POST",
            dataType: "json",
            data: {
                "value1": document.getElementById('questionValue1').value,
                "value2": document.getElementById('questionValue2').value
            },
            async: true,
            success: function (data) {
                if(data.type === 'gaugeDelete') {
                    replaceChart(data);
                    hideAllSections(false);
                    __showEditGaugeSection(data.tab);
                }
            }
        });
    }
});

