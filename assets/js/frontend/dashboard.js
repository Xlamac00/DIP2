
$(document).ready(function() {

    var createBtnClicked = false;
    var createBtnError = false;
    var dashboardIssueCreateBtn = document.getElementById('dashboardIssueCreateBtn');
    var dashboardIssueCloseBtn = document.getElementById('dashboardIssueNameClose');
    var dashboardIssueName = document.getElementById('dashboardIssueName');
    dashboardIssueName.onfocus = function () {
        dashboardIssueName.classList.remove("borderless");
        var buttons = document.getElementById('dashboardIssueButtons');
        buttons.style.display = 'block';
    };
    dashboardIssueName.onblur = function () {
        if(createBtnClicked === false)
            hideDashboardIssueName();
    };
    dashboardIssueName.onkeypress = function (e) {
        e = e || window.event;
        if (e.keyCode === 13) { // Enter
            dashboardIssueCreateConfirm();
        }
    };
    function hideDashboardIssueName() {
        dashboardIssueName.classList.remove('is-invalid');
        dashboardIssueName.classList.add("borderless");
        var buttons = document.getElementById('dashboardIssueButtons');
        buttons.style.display = 'none';
    }
    dashboardIssueCreateBtn.onmousedown = function() {
        createBtnClicked = true;
        dashboardIssueCreateConfirm()
    };
    function dashboardIssueCreateConfirm() {
        if(dashboardIssueName.value.length <= 0) {
            dashboardIssueName.classList.add('is-invalid');
            createBtnError = true;
        }
        else {
            ajaxNewIssue();
            createBtnError = false;
            dashboardIssueName.value = '';
            dashboardIssueName.classList.remove('is-invalid');
            hideDashboardIssueName();
        }
    }
    dashboardIssueCreateBtn.onmouseup = function () {
        if (createBtnError === false) {
            createBtnClicked = false;
            hideDashboardIssueName();
        }
    };
    dashboardIssueCloseBtn.onclick = function () {
        createBtnClicked = false;
        dashboardIssueName.value = '';
        hideDashboardIssueName();
    };

    function ajaxNewIssue() {
        var board = document.getElementById('dashboardId');
        // var cards = document.getElementById('dashboardNewCardPlaceholder');
        $.ajax({
            url: '/ajax/issueNew',
            type: "POST",
            dataType: "json",
            data: {
                "name": dashboardIssueName.value,
                "board": board.value
            },
            async: true,
            success: function (data) {
                location.href = '/issue/'+data.link;
                // cards.style.display = 'flex';
                // cards.innerHTML = data.card;
                // console.log(data);
            }
        });
    }

    $('.issueDelete').click(ajaxDeleteIssue);
    function ajaxDeleteIssue() {
        $.ajax({
            url: '/ajax/issueDelete',
            type: "POST",
            dataType: "json",
            data: {
                "link": this.name
            },
            async: true,
            success: function (data) {
                var elem =  document.getElementById('issue-'+data.link);
                elem.parentNode.removeChild(elem);
            }
        });
    }

});