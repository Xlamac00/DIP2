
$(document).ready(function() {
    /** ********************************************************************************* **
     ** **************************** ISSUE CREATE AND DELETE **************************** **
     ** ********************************************************************************* **/
    var createBtnClicked = false;
    var createBtnError = false;
    var dashboardIssueCreateBtn = document.getElementById('dashboardIssueCreateBtn');
    var dashboardIssueCloseBtn = document.getElementById('dashboardIssueNameClose');
    var dashboardIssueName = document.getElementById('dashboardIssueName');
    if(dashboardIssueName !== null) {
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
    }
    function ajaxNewIssue() {
        var board = document.getElementById('boardId');
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
                location.href = '../../'+data.link;
            }
        });
    }
    // One modal window to delete any Issue - on open insert issue name and id
    $('#modalEntityDelete').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var name = button.data('name');
        var id = button.data('id');
        var type = button.data('type');
        var color = button.data('color');
        var modal = $(this);
        var header = document.getElementById('modalEntityDeleteHeader');
        header.className = 'modal-header text-white bg-'+color.substr(1);
        modal.find('.modal-title').text('Delete ' + name);
        modal.find('#modalDeleteQuestion').text('Do you really want to delete ' + name + '?');
        document.getElementById('modalIssueDeleteId').value = id;
        document.getElementById('modalEntityDeleteType').value = type;
        document.getElementById('modalEntityDeleteBtn').innerHTML = 'Delete ' + type;
    });
    document.getElementById('modalEntityDeleteBtn').onclick = function () {
        var type = document.getElementById('modalEntityDeleteType').value;
        var link = document.getElementById('modalIssueDeleteId').value;
        var loading = document.getElementById('modalIssueDeleteLoading');
        if(link.length > 0) {
            loading.className = 'd-block';
            if(type === 'project') {
                $.ajax({
                    url: '/ajax/boardDelete',
                    type: "POST",
                    dataType: "json",
                    data: {
                        "board": link
                    },
                    async: true,
                    success: function (data) {
                        console.log(data);
                        location.href = '../../dashboard';
                    }
                });
            }
            else if(type === 'issue') {
                $.ajax({
                    url: '/ajax/issueDelete',
                    type: "POST",
                    dataType: "json",
                    data: {
                        "value1": link
                    },
                    async: true,
                    success: function (data) {
                        document.getElementById('issueCard'+data.link+'NormalSection').className = 'd-none';
                        document.getElementById('issueCard'+data.link+'DeletedSection').className = 'd-block';
                        $('#modalEntityDelete').modal('hide');
                        loading.className = "d-none";
                    }
                });
            }
        }
    };
    $('.issueCardRestore').click(function () {
        if(this.name.length > 0) {
            var loading = document.getElementById('issueCard'+this.name+'RestoreLoading');
            loading.className = 'd-block';
            $.ajax({
                url: '/ajax/issueRestore',
                type: "POST",
                dataType: "json",
                data: {
                    "link": this.name
                },
                async: true,
                success: function (data) {
                    console.log(data);
                    document.getElementById('issueCard'+data.link+'NormalSection').className = 'd-block';
                    document.getElementById('issueCard'+data.link+'DeletedSection').className = 'd-none';
                    loading.className = "d-none";
                }
            });
        }
    });
});