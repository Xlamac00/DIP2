
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
        var operation = button.data('operation');
        var modal = $(this);
        var header = document.getElementById('modalEntityDeleteHeader');
        header.className = 'modal-header text-white bg-'+color.substr(1);
        if(operation === 'delete') {
            modal.find('.modal-title').text('Delete ' + name);
            modal.find('#modalDeleteQuestion').text('Do you really want to delete ' + name + '?');
            document.getElementById('modalEntityDeleteBtn').innerHTML = 'Delete ' + type;
        }
        else if(operation === 'archive') {
            modal.find('.modal-title').text('Archive ' + name);
            document.getElementById('modalDeleteQuestion').innerHTML = 'Do you really want to archive ' + name + '?<br>' +
                '<p class="text-secondary">Archived project can still be seen, but cannot be edited.</p>';
            document.getElementById('modalEntityDeleteBtn').innerHTML = 'Archive project';
        }
        document.getElementById('modalIssueDeleteId').value = id;
        document.getElementById('modalEntityDeleteType').value = type;
    });
    var deleteEntity = document.getElementById('modalEntityDeleteBtn');
    if(deleteEntity !== null) {
        deleteEntity.onclick = function () {
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
                        data: { "board": link },
                        async: true,
                        success: function () {
                            location.href = '../../dashboard';
                        }
                    });
                }
                else if(type === 'issue') {
                    $.ajax({
                        url: '/ajax/issueDelete',
                        type: "POST",
                        dataType: "json",
                        data: { "value1": link },
                        async: true,
                        success: function (data) {
                            document.getElementById('issueCard'+data.link+'NormalSection').className = 'd-none';
                            document.getElementById('issueCard'+data.link+'DeletedSection').className = 'd-block';
                            $('#modalEntityDelete').modal('hide');
                            loading.className = "d-none";
                        }
                    });
                }
                else if(type === 'archive') {
                    console.log('archiving'+link);
                    $.ajax({
                        url: '/ajax/boardArchive',
                        type: "POST",
                        dataType: "json",
                        data: { "board": link },
                        async: true,
                        success: function (data) {
                            console.log(data);
                            location.href = '../../dashboard';
                        }
                    });
                }
            }
        };
    }
    // One modal window to delete any Issue - on open insert issue name and id
    $('#modalEntityDuplicate').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        if(button !== null) {
            var id = button.data('id');
            var color = button.data('color').substr(1);
            var header = document.getElementById('modalDuplicateHeader');
            header.className = 'modal-header text-white bg-'+color;
            document.getElementById('modalDuplicateId').value = id;
        }
        $('#modalDuplicateDate input').datepicker({
            format: "dd/mm/yyyy",
            weekStart: 1,
            todayHighlight: true
        });
        document.getElementById('modalDuplicateName').focus();
    });
    var duplicateBoard = document.getElementById('modalDuplicateBtn');
    if(duplicateBoard !== null) {
        duplicateBoard.onclick = function () {
            var boardId = document.getElementById('modalDuplicateId');
            var name = document.getElementById('modalDuplicateName');
            var start = document.getElementById('modalDuplicateStart');
            var loading = document.getElementById('modalDuplicateLoading');
            if(name.value.length <= 1) name.classList.add('is-invalid');
            else name.classList.remove('is-invalid');
            if(start.value.length <= 0) start.classList.add('is-invalid');
            else start.classList.remove('is-invalid');
            if(name.value.length > 0 && start.value.length > 0) {
                console.log(name.value+","+boardId.value+";"+start.value);
                loading.className = 'd-block';
                $.ajax({
                    url: '/ajax/boardDuplicate',
                    type: "POST",
                    dataType: "json",
                    data: {
                        "board": boardId.value,
                        "name": name.value,
                        "start": start.value
                    },
                    async: true,
                    success: function (data) {
                        console.log(data);
                        loading.className = '';
                        location.href = '../../'+data.url;
                    }
                });
            }
        };
    }
    var leaveBoard = document.getElementById('modalLeaveBoardBtn');
    if(leaveBoard !== null) {
        leaveBoard.onclick = function () {
            var board = document.getElementById('boardId');
            var loading = document.getElementById('modalLeaveBoardLoading');
            loading.className = 'd-block';
            $.ajax({
                url: '/ajax/boardRemoveCurrentUser',
                type: "POST",
                dataType: "json",
                data: {
                    "board": board.value
                },
                async: true,
                success: function (data) {
                    console.log(data);
                    loading.className = '';
                    location.href = '../../dashboard';
                }
            });
        };
    }
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