
$(document).ready(function() {
    var boardId = document.getElementById('boardId').value;

    $('.startTooltip').tooltip();

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
                location.href = '../../'+data.link;
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

    /** ********************************************************************************* **
     ** **************************** BOARD SHARING **************************** **
     ** ********************************************************************************* **/
    // Functionality of the 'copy to clipboard' button
    var copyBtn = document.getElementById('modalBoardShareCopyBtn');
    if(copyBtn !== null) {
        copyBtn.onclick = function () {
            var copyText = document.getElementById("modalBoardShareLink");
            copyText.select();
            document.execCommand("Copy");
            var link = $('#modalBoardShareLink'); // show text it was copied and hide it after a while
            link.tooltip('show');
            setTimeout(function () {
                link.tooltip('dispose');
            }, 1000);
        };
    }
    // Disable the option to edit by anonymous users if its for read only
    var roleSelect = document.getElementById('modalBoardShareSelect');
    if(roleSelect !== null)
        roleSelect.onchange = function () {ajaxBoardChangeRights()};
    var anonCheck = document.getElementById('modalBoardShareAnonymous');
    if(anonCheck !== null)
        anonCheck.onchange = function () {ajaxBoardChangeRights()};
    function ajaxBoardChangeRights() {
        var option = roleSelect.options[roleSelect.selectedIndex].value;
        var anonymous = document.getElementById('modalBoardShareAnonymous');
        var loading = document.getElementById('modalBoardShareOptions');
        var loadingOld = loading.innerHTML;
        loading.innerHTML =  '<i class="fa fa-spinner fa-spin">';
        $.ajax({
            url: '/ajax/boardChangeShareRights',
            type: "POST",
            dataType: "json",
            data: {
                "option": option,
                "anonymous": anonymous.checked,
                "board": boardId
            },
            async: true,
            success: function (data) {
                var text = document.getElementById('modalBoardShareAnonymousText');
                if(data.option !== false) { // if all went right
                    if (data.option === 'ROLE_ISSUE_READ') {
                        anonymous.disabled = true;
                        text.className = 'text-secondary';
                    }
                    else {
                        anonymous.disabled = false;
                        text.className = 'text-black';
                    }
                    anonymous.checked = (data.option === 'ROLE_ISSUE_ANONWRITE');
                }
                loading.innerHTML = loadingOld;
            }
        });
    }
    var disableBtn = document.getElementById('modalBoardShareDisable');
    var enableBtn = document.getElementById('modalBoardShareEnable');
    if(disableBtn !== null) {
        disableBtn.onclick = function () {ajaxBoardChangeShare(false)};
        enableBtn.onclick = function () {ajaxBoardChangeShare(true)};
    }
    function ajaxBoardChangeShare(newValue) {
        $.ajax({
            url: '/ajax/boardChangeShare',
            type: "POST",
            dataType: "json",
            data: {
                "enable": newValue,
                "board": boardId
            },
            async: true,
            success: function (data) {
                var eSection = document.getElementById('modalBoardShareEnabledSection');
                var dSection = document.getElementById('modalBoardShareDisabledSection');
                if(data.enable === true) { // show section with link sharing option
                    eSection.style.display = 'block';
                    dSection.style.display = 'none';
                    document.getElementById("modalBoardShareOptions").style.display = 'block';
                    document.getElementById("modalBoardShareLink").select(); //select link text
                }
                else { // show section with link-sharing enable button
                    eSection.style.display = 'none';
                    dSection.style.display = 'block';
                    document.getElementById("modalBoardShareOptions").style.display = 'none';
                }
            }
        });
    }
    // On modal window open focus the share link text
    $("#modalShareBoard").on('shown.bs.modal', function(){
        var copyText = document.getElementById("modalBoardShareLink");
        copyText.select();

        initView();
    });

    function initView() {
        var removeBtn = $('.userRemove');
        removeBtn.click(ajaxBoardRemoveUser); // bind action to open gauge edit dialog
        removeBtn.tooltip('enable');
        $('.userChange').change(ajaxBoardChangeUser);
    }

    // toggle view in modal window - change between link sharing and individual user management
    var individualBtn = document.getElementById('modalBoardShareIndividual');
    if(individualBtn !== null) {
        individualBtn.onclick = function () {
            if (document.getElementById("modalBoardShareIndividualSection").style.display === 'none') {
                $.ajax({
                    url: '/ajax/boardGetUserlist',
                    type: "POST",
                    dataType: "json",
                    data: {
                        "board": boardId
                    },
                    async: true,
                    success: function (data) {
                        document.getElementById('modalBoardShareUserlist').innerHTML = data.result;
                        initView();
                        document.getElementById("modalBoardSharePrimarySection").style.display = 'none';
                        document.getElementById("modalBoardShareIndividualSection").style.display = 'block';
                        individualBtn.innerHTML = 'Return';
                    }
                });
            }
            else {
                document.getElementById("modalBoardSharePrimarySection").style.display = 'block';
                document.getElementById("modalBoardShareIndividualSection").style.display = 'none';
                individualBtn.innerHTML = 'Manage individual users';
            }
        };
    }

    function ajaxBoardChangeUser() {
        var loading = document.getElementById('modalBoardIndividualLoading');
        loading.style.display = 'block'; // show loading button
        var option = this.options[this.selectedIndex].value;
        var user = this.name;
        $.ajax({
            url: '/ajax/boardChangeUser',
            type: "POST",
            dataType: "json",
            data: {
                "user": user,
                "role": option,
                "board": boardId
            },
            async: true,
            success: function (data) {
                console.log(data);
                loading.style.display = 'none'; // hide loading
                var name = document.getElementById('modalBoardShareUser'+user);
                if(data.enabled === true)
                    name.className = '';
                else
                    name.className = 'text-secondary';
            }
        });
    }

    function ajaxBoardRemoveUser() {
        var loading = document.getElementById('modalBoardIndividualLoading');
        loading.style.display = 'block'; // show loading button
        var element = this;
        $('.userRemove').tooltip('dispose');
        $.ajax({
            url: '/ajax/boardRemoveUser',
            type: "POST",
            dataType: "json",
            data: {
                "user": element.name,
                "board": boardId
            },
            async: true,
            success: function (data) {
                console.log(data);
                loading.style.display = 'none'; // hide loading
                if(data.success === true) {
                    document.getElementById('modalBoardShareUserlist').innerHTML = data.result;
                    initView();
                }
            }
        });
    }

    // Add username to share - autocomplete
    // uses bootstrap jquery library
    // https://www.jqueryscript.net/form/jQuery-Bootstrap-4-Typeahead-Plugin.html
    $('#modalBoardShareAddUser').typeahead({
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
                    document.getElementById("modalBoardShareInviteBtn").style.display = 'block';
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

    document.onkeypress = function (e) {
        e = e || window.event;
        if (e.keyCode === 13) { // Enter
            if($('#modalBoardShareAddUser').is(':focus')){
                ajaxInviteUser();
            }
        }
    };

    var inviteBtn = document.getElementById('modalBoardShareInviteBtn');
    if(inviteBtn !== null)
        inviteBtn.onclick = function () {ajaxInviteUser()};
    function ajaxInviteUser () {
        var name = document.getElementById('modalBoardShareAddUser');
        // regex to match either email address, or my user from db (name@ ... )
        var re = /^.+\(.{2,}@ \.\.\. \)$|^.{2,}@[a-z0-9]{2,}\.[a-z0-9]+$/i;
        if(name.value.match(re)) {
            var loading = document.getElementById('modalBoardInviteLoading');
            loading.style.display = 'block'; // show loading button
            name.className = 'form-control';
            var roleSelect = document.getElementById('modalBoardShareInviteRole');
            var option = roleSelect.options[roleSelect.selectedIndex].value;
            $.ajax({
                url: '/ajax/boardInviteUser',
                type: "POST",
                dataType: "json",
                data: {
                    "username": name.value,
                    "board": boardId,
                    "role": option
                },
                async: true,
                success: function (data) {
                    document.getElementById("modalBoardShareInviteBtn").style.display = 'none';
                    name.value = ''; // reset the input
                    loading.style.display = 'none'; // hide loading
                    var tip = $('#modalBoardShareAddUser');
                    tip.tooltip('show'); // show 'user invited' msg
                    setTimeout(function(){
                        tip.tooltip('dispose');
                    }, 3500);
                }
            });
        }
        else {
            name.className = 'form-control is-invalid';
        }
    };
});