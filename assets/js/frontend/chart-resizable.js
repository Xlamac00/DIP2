
$(document).ready(function() {
    var userId = document.getElementById("user-id").value;
    var canvas = document.getElementById("animatedChart");
    var changeActive = false;
    var commentActive = false;
    var changeBar = false;
    var changeOld = [];

    function initCanvasListeners(canvas) {
        canvas.onmousemove = function (evt) { chartMouseMoveEvent(evt.offsetY);};
        canvas.ontouchmove = function (evt) { chartTouchMoveEvent(evt);};
        canvas.ontouchstart = function (evt) { chartTouchDownEvent(evt);};
        canvas.onmousedown = function (evt) { chartMouseDownEvent(evt.offsetX, evt.offsetY, false);};
        canvas.onmouseup = function () { chartMouseUpEvent();};
        canvas.ontouchend = function () { chartMouseUpEvent();};
    }
    initCanvasListeners(canvas);

    // gets rights to edit individual Gauges
    var gaugeRights = [];
    document.getElementById('gaugeRights').childNodes.forEach(function(item){
        gaugeRights.push(item.dataset.rights === '1');
    });

    /** *****************************************************************************************************
     * ************************************* RESIZABLE CHART FUNCTIONS ************************************ *
     ***************************************************************************************************** **/

    function chartTouchMoveEvent(evt) {
        evt.preventDefault();
        var touch = evt.touches[0] || evt.changedTouches[0];
        var y = touch.pageY-findPosition(canvas, "top");
        // calculate position for touch and then work as same as mouse
        chartMouseMoveEvent(y);
    }

    // Redraws the bar depending on the cursor position
    function chartMouseMoveEvent(offsetY) {
        if (changeActive === true) { //the mouse was pressed over a graph bar
            var base = (getChartMetaData().data[changeBar]._model.base);
            var perc = (base - offsetY) / (base / 100);
            if (perc < 2 || perc > 98) { // redraw and stop
                var oldPerc = (base - changeOld['y']) / (base / 100);
                if(oldPerc <= 2.5 && perc <= 2 && perc >= 0) {// old value was also low -> unintended behaviour
                    // updateGraphValue(false, perc);
                    return;
                }
                console.log("Ending active mode"+perc+","+oldPerc+','+oldValue);
                perc = perc <= 2 ? 2 : 100;
                changeActive = false;
                if(oldValue !== null && oldValue == 2 && perc === 2)
                    updateGraphValue(false, perc);
                else {
                    console.log('sending: '+perc+','+oldValue);
                    ajaxUpdateGraph(perc);
                }
                // else
                //     updateGraphValue(false, perc);
            }
            else { // redraw the graph
                chart.data.datasets[0].data[changeBar] = perc;
                updateGraphValue(false, perc);
            }
        }
    }

    function chartTouchDownEvent(evt) {
        evt.preventDefault();
        var touch = evt.touches[0] || evt.changedTouches[0];
        var x = touch.pageX-findPosition(canvas, "left");
        var y = touch.pageY-findPosition(canvas, "top");
        // calculate position for touch and then work as same as mouse
        chartMouseDownEvent(x, y, true);
    }

    // Calculates elements position from top of the screen
    // @param element
    // @param dimension - "left" or "top"
    function findPosition(element, dimension) {
        var node = element;
        var curtop = 0;
        if (node.offsetParent) {
            do {
                if(dimension === 'top')
                    curtop += node.offsetTop;
                else
                    curtop += node.offsetLeft;
            } while (node = node.offsetParent);
            return curtop;
        }
    }

    // Checks if the mouse was pressed over a graph bar and if so,
    // switches the bar into active mode and saves the coordinates
    // of original bar to draw the old line
    var oldValue = null;
    function chartMouseDownEvent(offsetX, offsetY, touchScreen) {
        var bar = wasClickedOnBar(offsetX, offsetY, touchScreen);
        // check if user has rights to edit this gauge
        if (bar !== false && commentActive === false && gaugeRights[bar] === true) {
            var coords = getChartMetaData().data[bar]._model;
            changeActive = true;
            changeBar = bar;
            oldValue = chart.data.datasets[0].data[changeBar];
            changeOld = {
                "x1": (coords.x - coords.width / 2),
                "x2": (coords.x + coords.width / 2),
                "y": (coords.y)
            };
        }
        else {
            console.log('mouser down false'+commentActive+";"+gaugeRights[bar]);
            changeActive = false;
            changeBar = false;
        }
    }
    // Ends the active mode
    function chartMouseUpEvent() {
        if (changeActive) {
            changeActive = false;
            var newValue = chart.data.datasets[0].data[changeBar];
            if(oldValue !== null && oldValue !== newValue) {
                ajaxUpdateGraph(newValue);
            }
        }
    }

    // Checks the coordinates of mouse click with dimensions of bars in graph
    function wasClickedOnBar(x, y, touchScreen) {
        for (var i = 0; i < getChartMetaData().data.length; i++) {
            var bar = getChartMetaData().data[i]._model;
            if (x > (bar.x - bar.width / 2) && x < (bar.x + bar.width / 2)) { // click on graph on X coords
                var hitBox = 5;
                if(touchScreen === true && (bar.base - bar.y) < 15) // using touch screen and graph is low
                    hitBox = 50;
                if(y + hitBox > bar.y && y - 5 < bar.base) // click on graph Y coords
                    return i;
            }
        }
        console.log('was not clicked on bar');
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
        for(var i = 0; true; i++) {
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
                    label: data.names.slice(0, -1).replace(/'/g,'').split(','),
                    borderWidth: 1  }]
            },
            options: options
        });
        if(typeof data.gaugeRights !== 'undefined') {
            gaugeRights = [];
            data.gaugeRights.forEach(function (item) {
                gaugeRights.push(item === 1);
            });
            console.log(gaugeRights);
        }

        // Item was replaced, so all the events must be registered again
        var canvas = document.getElementById("animatedChart");
        initCanvasListeners(canvas);
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
                chart.ctx.moveTo(changeOld['x1'], changeOld['y']);
                chart.ctx.strokeStyle = '#222';
                chart.ctx.lineTo(changeOld['x2'], changeOld['y']);
                chart.ctx.stroke();
            }
            else if(commentActive) {
                chart.ctx.beginPath();
                chart.ctx.moveTo(changeOld['x1'], changeOld['y']);
                chart.ctx.strokeStyle = '#666';
                chart.ctx.lineTo(changeOld['x2'], changeOld['y']);
                chart.ctx.stroke();
            }
        }
    });

    /** *****************************************************************************************************
     * ******************************************* KEY BINDINGS ******************************************* *
     ***************************************************************************************************** **/

    // Key shortcuts to commit or discard changes
    document.onkeypress = function (e) {
        e = e || window.event;
        if (e.keyCode === 27) { // Escape
            if(commentActive)       // add new comment dialog is opened
                ajaxDiscardChange(); // discard latest commit change
            else if(previousSection.length > 0) // there is any section to hide
                hideCurrentSection();
            else if(previousSection.length === 0 // hide all additional comments
                && document.getElementById('gaugeCommentShowAllBtn').getAttribute('data-field') === 'hide')
                var event = new CustomEvent('toggleAllComments', {cancelable: true, bubbles: true});
                if(typeof event === 'object') document.dispatchEvent(event);
        }
        else if (e.keyCode === 13) { // Enter
            if (commentActive
                && (!$('#gaugeCommentText').is(':focus') || (e.ctrlKey && $('#gaugeCommentText').is(':focus')))) // user is not writing
                ajaxCommentChange();      // save the gauge change to DB
            else if (document.getElementById('gaugeNewSection').style.display === 'block') // dialog to add new gauge
                ajaxSaveNewGauge();
            else if (document.getElementById('gaugeEditGaugeSection').style.display === 'block') {
                if ($('.gaugeEditInput').is(':focus')) {// dialog to edit gauge name
                    var input = $('.gaugeEditText :focus');
                    updateGaugeName(input.parent().parent().get(0), input.parent().get(0).id.substr(1), '');
                }
                else if ($('.gaugeEditOwnerInput').is(':focus')) {// dialog to add gauge editor
                    var focused = $(':focus');
                    ajaxInviteUserToGauge(focused[0].id.substr(1), focused[0].value);
                    focused.blur();
                }
            }
            else if (document.getElementById('IssueDeadlinesSection').style.display === 'block') { // creating new
                // deadline
                if(e.ctrlKey && $('#deadlineTextarea').is(':focus'))
                    ajaxSaveNewDeadline();
            }
            else if(document.getElementById('questionSection').style.display === 'block') // question dialog
                ajaxSendQuestion();
            else if(document.getElementById('gaugeEditIssueSection').style.display === 'block') // issue update dialog
                ajaxUpdateIssue();
        }
    };

    /** *****************************************************************************************************
     * ****************************************** BUTTONS AND UI ****************************************** *
     ***************************************************************************************************** **/
    var previousSection = []; // history of shown sections

    /** Checks the number of gauges and if there are more then const, hides the button
     * to disallow adding more gauges.
     * Also checks and if there are no gauges, opens the add new gauge dialog instead of comments overview.
     *
     * @param count (nullable) - number of gauges, if not set, gets them from the button value
      */
    function hideAddNewGaugesBtn(count) {
        if(typeof count === 'undefined') { //get count of gauges set by the controller
            var button = document.getElementById('gaugeAddNewBtn');
            if(button === null) return;
            count = button.value;
        }
        if(count <= 0) { // there are no gauges, show create new dialog
            hideAllSections(false);
            document.getElementById('gaugeNewSection').style.display = 'block';
            document.getElementById('gaugeAddNewName').focus();
        }
        else if(count >= 4) { // not allowed to have more then 4 gauges
            document.getElementById('gaugeAddNewBtn').disabled = true;
        }
        else {// show the button normally
            document.getElementById('gaugeAddNewBtn').disabled = false;
        }
    }
    hideAddNewGaugesBtn();

    /** Closes the currently visible section and shows the previous one.
     * @see hideAllSections()
     */
    $('.gaugeCloseBtn').click(hideCurrentSection);
    function hideCurrentSection() {
        var previous = previousSection.pop();
        var hidden = hideAllSections(false); // dont push the previous section into the queue!
        if(hidden === previous) { // dont show one tab 2 times in the row, call again
            hideCurrentSection();
            return;
        }
        var section = $("#gaugeSections div.section");
        if(typeof section[previous] === 'undefined')
            previous = 0;
        section[previous].style.display = 'block'; // display the previous one
    }

    function deadlineDelete(e) {
        console.log('delte');
        hideAllSections();
        $('.startTooltip').tooltip('hide');
        console.log(e.currentTarget);
        document.getElementById('questionText').innerHTML = 'delete this deadline';
        document.getElementById('questionCall').value = '/ajax/issueDeleteDeadline';
        document.getElementById('questionValue1').value = e.currentTarget.value;
        document.getElementById('questionValue2').value = '';
        document.getElementById('questionSection').style.display = 'block';
    }

    function updateDeadlineSelect() {
        var deadlineSelect = document.getElementById('deadlineTasks');
        var option = deadlineSelect.options[deadlineSelect.selectedIndex].value;
        var trash = document.getElementById('deadlineTrash');
        var data = $('#deadlinesData #deadline'+option);
        if(typeof data.children().get(0) !== 'undefined') {
            document.getElementById('deadlineStart').value =
                (typeof data.children().get(0) !== 'undefined') ? data.children().get(0).value : '';
            document.getElementById('deadlineEnd').value =
                (typeof data.children().get(1) !== 'undefined') ? data.children().get(1).value : '';
            document.getElementById('deadlineTextarea').innerHTML =
                (typeof data.children().get(2) !== 'undefined') ? data.children().get(2).value : '';
            trash.classList.remove('d-none');
            trash.value = data.children().get(3).value;
            trash.onclick = function (e) {deadlineDelete(e);};
        }
        else {
            document.getElementById('deadlineStart').value = '';
            document.getElementById('deadlineEnd').value = '';
            document.getElementById('deadlineTextarea').innerHTML = '';
            trash.classList.add('d-none');
            trash.onclick = null;
        }
    }

    function replaceDeadlineItems(list, select) {
        var oldText = document.getElementById('issueDeadlines');
        oldText.innerHTML =  list;
        var els = oldText.getElementsByClassName('deadlineDelete');
        [].forEach.call(els, function (el) { // bind delete button with ajax action
            el.onclick = function (e) {
                deadlineDelete(e);
            };
        });
        if(select !== null) {
            document.getElementById('deadlinesSelect').innerHTML = select;
            var deadlineSelect = document.getElementById('deadlineTasks');
            if(deadlineSelect !== null)
                deadlineSelect.onchange = function () {updateDeadlineSelect()};
        }
    }
    replaceDeadlineItems(document.getElementById('issueDeadlines').innerHTML, document.getElementById('deadlinesSelect').innerHTML);

    /** Hides all sections and remembers the last one visible.
     * The last visible section is pushed into the queue to remember the history.
     *
     * @param push (implicit true) - if true, remembers the last section.
     *
     * @return number - id of the section hidden by this function
     */
    function hideAllSections(push) {
        push = typeof push !== 'undefined' ? push : true;
        // make all other sections invisible
        var sections = $("#gaugeSections div.section");
        var hidden = 0;
        for(var i = 0; i < sections.length; i++) {
            if((sections[i]).style.display === 'block') {
                hidden = i;
                if(push === true) { // save hidden section
                    var top = previousSection.pop();
                    if (top !== 'undefined')
                        previousSection.push(top);
                    if (top !== i) // kontrola, ze nevkladam 2x to stejne navrch
                        previousSection.push(i);
                }
            }
            (sections[i]).style.display = 'none';
        }
        window.scrollTo(0,0);
        return hidden;
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
            var text = document.getElementById('gaugeAddNewName');
            text.focus();
        }
    }

    /** Displays section with gauge edit options
     */
    $('#dropdownGaugeBtn').click(showEditGauge);
    function showEditGauge() {
        hideAllSections();
        ajaxGetGaugesInfo();
        $('#dropdownGaugeBtn').blur();
    }

    /** Displays section with issue edit options
     */
    $('#dropdownIssueBtn').click(showEditIssue);
    function showEditIssue() {
        hideAllSections();
        document.getElementById('gaugeEditIssueSection').style.display = 'block'; // make issue edit section visible
        var text = document.getElementById('issueEditName');
        text.select();
    }

    /** Displays section to add new deadline
     */
    $('#dropdownDeadlineNew').click(showNewDeadline);
    function showNewDeadline() {
        $('#deadlinesDatepicker .input-daterange').datepicker({
            format: "dd/mm/yyyy",
            weekStart: 1,
            todayHighlight: true
        });
        hideAllSections();
        updateDeadlineSelect();
        document.getElementById('IssueDeadlinesSection').style.display = 'block'; // make issue edit section visible
        this.blur();
        var tip = document.getElementById('deadlinesTipBig');
        if(tip !== null) { // show deadlines tip
            var event = new CustomEvent('showTip', {detail: {element: "deadlines"}, cancelable: true, bubbles: true});
            tip.dispatchEvent(event);
        }
    }

    /** Displays section with reminders - download content first
     */
    $('#dropdownRemindersBtn').click(showReminders);
    function showReminders() {
        var section = document.getElementById('IssueReminderSection');
        hideAllSections();
        section.style.display = 'block';
        $.ajax({
            url:'/ajax/issueGetReminder',
            type: "POST",
            dataType: "json",
            data: {"issueId": document.getElementById('issueId').value},
            async: true,
            success: function (data) {
                section.innerHTML = data.render;
                $('#reminderSaveBtn').click(ajaxSaveReminder); // set function call from btn
                $('#reminderTextareaBtn').click(showReminderTextarea);
                $('.gaugeCloseBtn').click(hideCurrentSection);
                var tip = document.getElementById('remindersTipBig');
                if(tip !== null) { // show deadlines tip
                    var event = new CustomEvent('showTip', {detail: {element: "reminders"}, cancelable: true, bubbles: true});
                    tip.dispatchEvent(event);
                }
            }
        });
    }

    $('#questionNo').click(hideCurrentSection);
    $('#questionYes').click(ajaxSendQuestion);
    function showGaugeDeleteDialog() {
        hideAllSections();
        $('.gaugeDelete').blur();
        document.getElementById('questionText').innerHTML = 'delete this task';
        document.getElementById('questionCall').value = '/ajax/issueGaugeDelete';
        document.getElementById('questionValue1').value = this.name;
        document.getElementById('questionValue2').value = chart.config.issueId;
        document.getElementById('questionSection').style.display = 'block';
    }

    var settingsBtn = document.getElementById('gaugeEditBtn');
    if(settingsBtn !== null) {
        settingsBtn.onclick = function () {
            $('#settingsTooltip').tooltip('dispose');
        };
        settingsBtn.onblur = function () {
            $('#settingsTooltip').tooltip('enable');
        };
    }

    /** *****************************************************************************************************
     * ******************************************** AJAX CALLS ******************************************** *
     ***************************************************************************************************** **/

    $('#gaugeAddNewSaveBtn').click(ajaxSaveNewGauge);
    function ajaxSaveNewGauge() {
        var name = $('#gaugeAddNewName').val();
        var issue = chart.config.issueId;
        if(name.length > 0) {
            document.getElementById('gaugeAddNewName').className = 'form-control';
            $.ajax({
                url: "/ajax/issueNewGauge",
                type: "POST",
                dataType: "json",
                data: {
                    "userId": userId,
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
                    hideAddNewGaugesBtn(data.gaugeCount); //potentially hide add new gauge button
                    replaceDeadlineItems(data.deadlines, data.select);
                    var tip = document.getElementById('editGaugeTipBig');
                    if(tip !== null && data.gaugeCount === 1) { // show deadlines tip
                        var event = new CustomEvent('showTip', {detail: {element: "editGauge"}, cancelable: true, bubbles: true});
                        tip.dispatchEvent(event);
                    }
                }
            });
        }
        else // field name is empty
            document.getElementById('gaugeAddNewName').className = 'form-control is-invalid';
    }

    $('#issueDeadlineSaveBtn').click(ajaxSaveNewDeadline);
    function ajaxSaveNewDeadline() {
        var start = document.getElementById('deadlineStart');
        var end = document.getElementById('deadlineEnd');
        if(end.value.length > 0) {
            var endSplit = end.value.split("/");
            var dt = new Date(parseInt(endSplit[2], 10),
                parseInt(endSplit[1], 10) - 1,
                parseInt(endSplit[0], 10) + 1);
            if(dt.getTime() < Date.now()) {
                end.classList.add('is-invalid');
                return;
            }
            else {
                end.classList.remove('is-invalid');
            }
        }
        if(start.value.length > 0 && end.value.length > 0 && start.value !== end.value) {
            var issue = document.getElementById('issueId').value;
            var s = document.getElementById('deadlineTasks');
            var g = s.options[s.selectedIndex];
            var t = document.getElementById('deadlineTextarea');
            $.ajax({
                url: '/ajax/issueNewDeadline',
                type: "POST",
                dataType: "json",
                data: {
                    "issueId": issue,
                    "start": start.value,
                    "end": end.value,
                    "text": t.value,
                    "gauge": g.value
                },
                async: true,
                success: function (data) {
                    start.value = '';
                    end.value = '';
                    t.value = '';
                    replaceDeadlineItems(data.list, data.select);
                    start.classList.remove('is-invalid');
                    end.classList.remove('is-invalid');
                    hideAllSections();
                    $("#gaugeCommentSection").css('display', 'block');
                }
            });
        }
        else {
            start.classList.add('is-invalid');
            end.classList.add('is-invalid');
        }
    }

    $('#gaugeChangeResetBtn').click(ajaxDiscardChange); // set function call from btn
    /** Sends ajax request to discard last gauge change from db.
     *
     */
    function ajaxDiscardChange() {
        $.ajax({
            url: "/ajax/issueGraphDiscard",
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
                window.scrollTo(0,0);
            }
        });
    }

    $('#gaugeChangeConfirmBtn').click(ajaxCommentChange); // set function call from btn
    function ajaxCommentChange() {
        $.ajax({
            url: "/ajax/issueGraphComment",
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
                window.scrollTo(0,0);
                chart.update();
                var oldHtml = document.getElementById('gaugeComments').innerHTML;
                document.getElementById('gaugeComments').innerHTML = data + oldHtml;
                var noChangesText = document.getElementById('gaugeCommentNoChangesText');
                if(typeof noChangesText !== 'undefined' && noChangesText !== null)
                    noChangesText.className = "d-none";
            }
        });
    }

    /** Sends ajax request to save new graph value to db.
     *
     */
    function ajaxUpdateGraph(newValue) {
        $.ajax({
            url: "/ajax/issueGraphChange",
            type: "POST",
            dataType: "json",
            data: {
                "userId": userId,
                "issueId": chart.config.issueId,
                "gaugeNumber": changeBar,
                "gaugeValue": newValue
            },
            async: true,
            success: function (data) {
                commentActive = true;
                updateGraphValue(true, data.newValue); // redraw graph value and stop
                $("#gaugeCommentHeadline").css('color', data.color);
                document.getElementById('gaugeCommentHeadline').innerHTML =
                    data.name + ": " + data.oldValue + " <i class=\"fas fa-angle-right\"></i> <b>" + data.newValueText + "</b> %";
                hideAllSections();
                $("#gaugeCommentSection").css('display', 'block');
                $("#gaugeChangeCommit").css('display', 'flex');
                document.getElementById('gaugeCommentText').focus();
                var tip = document.getElementById('makeCommentTipBig');
                if(tip !== null) { // show deadlines tip
                    var event = new CustomEvent('showTip', {detail: {element: "makeComment"}, cancelable: true, bubbles: true});
                    tip.dispatchEvent(event);
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown ) {
                console.log(textStatus+","+errorThrown);
            }
        });
    }

    function ajaxGetGaugesInfo() {
        $.ajax({
            url: "/ajax/issueGetGauges",
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
        $('.editTooltip').tooltip();
        $('.gaugeDelete').click(showGaugeDeleteDialog); // bind action to open gauge edit dialog

        var oldName = null;
        // click on gauge name - show edit input, hide colorful name
        $('.gaugeEditName').click(function () {
            var name = this.parentNode.getElementsByClassName('gaugeEditName')[0];
            var editable = this.parentNode.getElementsByClassName('gaugeEditText')[0];
            editable.classList.remove('d-none');
            editable.getElementsByTagName('input')[0].select();
            name.classList.remove('d-inline-flex');
            name.classList.add('d-none');
            oldName = name.getElementsByTagName('h5')[0].innerHTML;
        });
        // focus outside gauge name edit - if the name is changed, save it on server
        $('.gaugeEditText').focusout(function () {
            updateGaugeName(this.parentNode, this.id.substr(1), oldName);
        });
        var oldColor = null;
        // click on gauge color - hide its color, show all available colors
        $('.gaugeEditColor').click(function () {
            var color = this.parentNode.getElementsByClassName('gaugeEditColor')[0];
            var colors = this.parentNode.getElementsByClassName('gaugeEditColors')[0];
            color.classList.remove('d-block');
            color.classList.add('d-none');
            colors.classList.remove('d-none');
            oldColor = $('#'+colors.id+" input:checked").get(0).value;
        });
        // click on gauge new color - if it was changed, save it on server
        $("#gaugeEditColorSection input[name='radio']").click(function() {
            var newColor = this.value;
            var gaugeId = this.parentNode.parentNode.id.substr(1);
            if(oldColor !== newColor) {
                this.parentNode.parentNode.innerHTML = '<i class="fas fa-2x fa-spinner fa-spin"></i>';
                ajaxUpdateGauge(gaugeId,null,newColor);
            }
            else {
                var color = this.parentNode.parentNode.parentNode.getElementsByClassName('gaugeEditColor')[0];
                color.classList.add('d-block');
                color.classList.remove('d-none');
                this.parentNode.parentNode.classList.add('d-none');
            }
        });
        $('.gaugeEditOwnerInput').focusin(function () {
            this.classList.add('border-secondary');
            this.classList.remove('border-muted');
            this.parentNode.getElementsByClassName('text-success')[0].classList.add('d-inline-flex');
        });
        $('.gaugeEditSend').click(function () {
            var input = this.parentNode.childNodes[0];
            ajaxInviteUserToGauge(input.id.substr(1), input.value);
        });
        $('.gaugeEditOwnerInput').typeahead({
            // data source - ajax query to user names
            source: function (query, process) {
                $.ajax({
                    url: '/ajax/autocompleteUsername',
                    type: "POST",
                    dataType: "json",
                    data: {
                        "input": query
                    },
                    async: true,
                    success: function (data) {
                        return process(data.result);
                    }
                });
            },
            // default template
            menu: '<ul class="typeahead dropdown-menu" role="listbox"></ul>',
            item: '<li><a class="dropdown-item" href="#" role="option"></a></li>',
            headerHtml: '<li class="dropdown-header"></li>',
            headerDivider: '<li class="divider" role="separator"></li>',
            itemContentSelector:'a',
            minLength: 2, // min length to trigger the suggestion list
            scrollHeight: 0, // number of pixels the scrollable parent container scrolled down
            autoSelect: true,// auto selects the first item
            afterSelect: $.noop,  // callbacks
            afterEmptySelect: $.noop,
            addItem: false, // adds an item to the end of the list
            delay: 0  // delay between lookups
        });
        initDraggableEntityRows();
    }

    var oldUser = null;
    function ajaxInviteUserToGauge(gaugeId, userName) {
        var input = document.getElementById("u"+gaugeId);
        if(userName !== oldUser || userName.length === 0) {
            var loading = document.getElementById("gaugeEditLoading"+gaugeId);
            loading.className = 'd-inline-flex';
            input.parentNode.getElementsByClassName('text-success')[0].classList.remove('d-inline-flex');
            $.ajax({
                url: "/ajax/issueInviteGauge",
                type: "POST",
                dataType: "json",
                data: {
                    "issueId": document.getElementById('issueId').value,
                    "gaugeId": gaugeId,
                    "userName": userName
                },
                async: true,
                success: function (data) {
                    replaceChart(data);
                    replaceDeadlineItems(data.list,null);
                    input.classList.remove('border-secondary');
                    input.value = data.user;
                    oldUser = data.user;
                    input.blur();
                    loading.className = 'd-none';
                    if(data.success === false)
                        input.classList.add('border-danger');
                    else {
                        input.classList.remove('border-danger');
                        input.classList.add('border-muted');
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown ) {
                    console.log(textStatus+","+errorThrown);
                    input.classList.remove('border-secondary');
                    input.classList.add('border-muted');
                    input.classList.add('is-invalid');
                    input.blur();
                    loading.className = 'd-none';
                }
            });
        }
        else {
            input.parentNode.getElementsByClassName('text-success')[0].classList.remove('d-inline-flex');
            input.classList.remove('border-secondary');
            input.classList.remove('border-danger');
            input.classList.add('border-muted');
        }
    }

    function updateGaugeName(parent, gaugeId, oldName) {
        var name = parent.getElementsByClassName('gaugeEditName')[0];
        var editable = parent.getElementsByClassName('gaugeEditText')[0];
        var newName = editable.getElementsByTagName('input')[0].value;
        if(newName !== oldName) {
            parent.innerHTML = '<i class="fas fa-2x fa-spinner fa-spin"></i>';
            ajaxUpdateGauge(gaugeId,newName,null);
        }
        else {
            editable.classList.add('d-none');
            name.classList.add('d-inline-flex');
            name.classList.remove('d-none');
        }

    }

    $('#issueEditSaveBtn').click(ajaxUpdateIssue); // set function call from btn
    function ajaxUpdateIssue() {
        var name = $('#gaugeEditIssueSection #issueEditName').val();
        if(name.length > 0) {
            $.ajax({
                url: "/ajax/issueUpdate",
                type: "POST",
                dataType: "json",
                data: {
                    "issueId": $("#gaugeEditIssueSection #issueEditId").val(),
                    "name": name
                },
                async: true,
                success: function (data) {
                    hideCurrentSection();
                    document.getElementById('issueName').innerHTML = data.name;
                }
            });
        }
        else // field name is empty
            document.getElementById('issueEditName').className = 'form-control is-invalid';
    }

    function ajaxSaveReminder() {
        var days = [document.getElementById("monday").checked, document.getElementById("tuesday").checked,
            document.getElementById("wednesday").checked, document.getElementById("thursday").checked,
            document.getElementById("friday").checked, document.getElementById("saturday").checked,
            document.getElementById("sunday").checked];
        var remind = document.getElementById("dayscheck").checked;
        var loading = document.getElementById("reminderLoading");
        loading.classList.remove('d-none');
        var users = [];
        $('.userChecklist input').each(function () { // iterate all users and add all unchecked to the array
            if(this.checked === false)
                users.push(this.value);
        });
        $.ajax({
            url: '/ajax/issueChangeReminder',
            type: "POST",
            dataType: "json",
            data: {
                "issueId": document.getElementById('issueId').value,
                "days": days,
                "users": users,
                "text": document.getElementById('reminderTextarea').value,
                "remind": remind
            },
            async: true,
            success: function (data) {
                loading.classList.add('d-none');
                hideCurrentSection();
            }
        });
    }

    function showReminderTextarea() {
        var area = document.getElementById('reminderTextarea');
        var arrow = document.getElementById('reminderTextareaArrow');
        if(area.classList[1] === 'd-none') {
            area.classList.remove('d-none');
            area.classList.add('d-block');
            arrow.classList.remove('fa-angle-double-down');
            arrow.classList.add('fa-angle-double-up');
        }
        else {
            area.classList.remove('d-block');
            area.classList.add('d-none');
            arrow.classList.remove('fa-angle-double-up');
            arrow.classList.add('fa-angle-double-down');
        }
    }

    $('#issueEditDelete').click(showIssueDeleteDialog);
    function showIssueDeleteDialog() {
        hideAllSections();
        $('.gaugeDelete').blur();
        document.getElementById('questionText').innerHTML = 'delete this issue';
        document.getElementById('questionCall').value = '/ajax/issueDelete';
        document.getElementById('questionValue1').value = document.getElementById('issueDeleteId').value;
        document.getElementById('questionValue2').value = '';
        document.getElementById('questionSection').style.display = 'block';
    }

    function ajaxUpdateGauge(gaugeId, newName, newColor) {
        $.ajax({
            url: "/ajax/issueUpdateGauge",
            type: "POST",
            dataType: "json",
            data: {
                "issueId": chart.config.issueId,
                "gaugeId": gaugeId,
                "name": newName,
                "color": newColor
            },
            async: true,
            success: function (data) {
                replaceChart(data);
                hideAllSections(false);
                __showEditGaugeSection(data.tab);
                var section = document.getElementById('gaugeCommentSection');
                section.innerHTML = data.comments;
                $('#gaugeChangeResetBtn').click(ajaxDiscardChange); // bind actions to the buttons (again)
                $('#gaugeChangeConfirmBtn').click(ajaxCommentChange);
                replaceDeadlineItems(data.deadlines, data.select);
                var event = new CustomEvent('toggleAllComments', {cancelable: true, bubbles: true});
                if(event !== null) section.dispatchEvent(event);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown ) {
                console.log(textStatus+","+errorThrown);
            }
        });
    }

    function ajaxSendQuestion() {
        $.ajax({
            url: document.getElementById('questionCall').value, // this value has to be set in question
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
                    replaceDeadlineItems(data.deadlines, data.select);
                    var commentSection = document.getElementById('gaugeCommentSection');
                    commentSection.innerHTML = data.comments;
                    $('#gaugeChangeResetBtn').click(ajaxDiscardChange); // bind actions to the buttons (again)
                    $('#gaugeChangeConfirmBtn').click(ajaxCommentChange);
                    var event = new CustomEvent('toggleAllComments', {cancelable: true, bubbles: true});
                    commentSection.dispatchEvent(event);
                    hideAddNewGaugesBtn(data.gaugeCount); //potentially show add new gauge button
                }
                else if(data.type === 'issueDelete') {
                    location.href = '../../'+data.return;
                }
                else if(data.type === 'deadlineDelete') {
                    replaceDeadlineItems(data.list, data.select);
                    hideAllSections(false);
                    $("#gaugeCommentSection").css('display', 'block');
                }
            }
        });
    }

    /** Drag n drop logic and ajax request.
     *  */
    //https://medium.com/@treetop1500/setting-up-a-sortable-drag-n-drop-interface-for-symfony-entities-7f0c84ac0c8e
    function initDraggableEntityRows() {
        var dragSrcEl = null; // the object being drug
        var entityId; // the id (key) of the entity

        function handleDragStart(e) {
            try {
                dragSrcEl = this.parentNode;
                entityId = $(this).attr('rel');
                dragSrcEl.style.opacity = '0.6';
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.parentNode.innerHTML);
            }
            catch(err) {
                e.preventDefault();
                dragSrcEl.style.opacity = '1';
                dragSrcEl = null;
                entityId = null;
            }
        }

        function handleDragOver(e) {
            if (e.preventDefault) {
                e.preventDefault(); // Necessary. Allows us to drop.
            }
            e.dataTransfer.dropEffect = 'move';  // See the section on the DataTransfer object.
            return false;
        }

        function handleDrop(e) {
            if (e.stopPropagation) {
                e.stopPropagation(); // stops the browser from redirecting.
            }
            console.log(this);
            if (dragSrcEl !== this) {// Don't do anything if dropping the same column we're dragging.
                var endPosition = $(this).attr('data-position');
                dragSrcEl.innerHTML = this.innerHTML;
                hideAllSections(false);
                $.ajax({
                    url: '/ajax/gaugeChangePosition',
                    type: "POST",
                    dataType: "json",
                    data: {
                        "issueId": chart.config.issueId,
                        "gaugeId": entityId,
                        "position": endPosition
                    },
                    async: true,
                    success: function (data) {
                        replaceChart(data);
                        __showEditGaugeSection(data.tab);
                    }
                });
            }
            return false;
        }
        function handleDragEnd() {
            this.style.opacity = '1';  // this / e.target is the source node.
        }
        var rows = document.querySelectorAll('div.sortable > div div div');
        [].forEach.call(rows, function(row) {
            row.addEventListener('dragstart', handleDragStart, false);
            row.addEventListener('dragover', handleDragOver, false);
            row.addEventListener('drop', handleDrop, false);
            row.addEventListener('dragend', handleDragEnd, false);
        });
    }
});

