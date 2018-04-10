
$(document).ready(function() {
    /** ********************************************************************************* **
     ** **************************** ISSUE SHARING **************************** **
     ** ********************************************************************************* **/
    // Download issue-share modal window and insert it into document
    $('#modalIssueShare').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var id = button.data('id');
        $.ajax({
            url: '/ajax/issueGetShareModal',
            type: "POST",
            dataType: "json",
            data: { "issue": id },
            async: true,
            success: function (data) {
                var modal = document.getElementById('modalIssueShare');
                modal.innerHTML = data.render;
                initEntitySharing('modal'+data.link, data.link);
                // var copyText = document.getElementById("modal"+data.link+"Link");
                // copyText.select();
                // initView();
            }
        });
    });
    /** ********************************************************************************* **
     ** **************************** ENTITY SHARING **************************** **
     ** ********************************************************************************* **/

    function initEntitySharing(name, entityId) {

        function initView() {
            var removeBtn = $('.userRemove');
            removeBtn.click(ajaxBoardRemoveUser); // bind action to open gauge edit dialog
            removeBtn.tooltip('enable');
            $('.userChange').change(ajaxBoardChangeUser);
        }

        // Functionality of the 'copy to clipboard' button
        var copyBtn = document.getElementById(name+'CopyBtn');
        if(copyBtn !== null) {
            copyBtn.onclick = function () {
                var copyText = document.getElementById(name+"Link");
                copyText.select();
                document.execCommand("Copy");
                var link = $('#'+name+'Link'); // show text it was copied and hide it after a while
                link.tooltip('show');
                setTimeout(function () {
                    link.tooltip('dispose');
                }, 1000);
            };
        }

        // Disable the option to edit by anonymous users if its for read only
        var roleSelect = document.getElementById(name+'Select');
        if(roleSelect !== null)
            roleSelect.onchange = function () {ajaxBoardChangeRights()};
        var anonCheck = document.getElementById(name+'Anonymous');
        if(anonCheck !== null)
            anonCheck.onchange = function () {ajaxBoardChangeRights()};
        function ajaxBoardChangeRights() {
            var option = roleSelect.options[roleSelect.selectedIndex].value;
            var anonymous = document.getElementById(name+'Anonymous');
            var loading = document.getElementById(name+'Options');
            var loadingOld = loading.innerHTML;
            loading.innerHTML =  '<i class="fa fa-spinner fa-spin"></i>';
            $.ajax({
                url: '/ajax/entityChangeShareRights',
                type: "POST",
                dataType: "json",
                data: {
                    "option": option,
                    "anonymous": anonymous.checked,
                    "entity": entityId
                },
                async: true,
                success: function (data) {
                    var text = document.getElementById(name+'AnonymousText');
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
        var disableBtn = document.getElementById(name+'Disable');
        var enableBtn = document.getElementById(name+'Enable');
        if(disableBtn !== null) {
            disableBtn.onclick = function () {ajaxBoardChangeShare(false)};
            enableBtn.onclick = function () {ajaxBoardChangeShare(true)};
        }
        function ajaxBoardChangeShare(newValue) {
            $.ajax({
                url: '/ajax/entityChangeShare',
                type: "POST",
                dataType: "json",
                data: {
                    "enable": newValue,
                    "entity": entityId
                },
                async: true,
                success: function (data) {
                    var eSection = document.getElementById(name+'EnabledSection');
                    var dSection = document.getElementById(name+'DisabledSection');
                    if(data.enable === true) { // show section with link sharing option
                        eSection.style.display = 'block';
                        dSection.style.display = 'none';
                        document.getElementById(name+"Options").style.display = 'block';
                        document.getElementById(name+"Link").select(); //select link text
                    }
                    else { // show section with link-sharing enable button
                        eSection.style.display = 'none';
                        dSection.style.display = 'block';
                        document.getElementById(name+"Options").style.display = 'none';
                    }
                }
            });
        }
        // toggle view in modal window - change between link sharing and individual user management
        var individualBtn = document.getElementById(name+'Individual');
        if(individualBtn !== null) {
            individualBtn.onclick = function () {
                if (document.getElementById(name+"IndividualSection").style.display === 'none') {
                    $.ajax({
                        url: '/ajax/entityGetUserlist',
                        type: "POST",
                        dataType: "json",
                        data: {
                            "entity": entityId
                        },
                        async: true,
                        success: function (data) {
                            document.getElementById(name+'Userlist').innerHTML = data.result;
                            initView();
                            document.getElementById(name+"PrimarySection").style.display = 'none';
                            document.getElementById(name+"IndividualSection").style.display = 'block';
                            individualBtn.innerHTML = 'Return';
                        }
                    });
                }
                else {
                    document.getElementById(name+"PrimarySection").style.display = 'block';
                    document.getElementById(name+"IndividualSection").style.display = 'none';
                    individualBtn.innerHTML = 'Manage individual users';
                }
            };
        }

        function ajaxBoardChangeUser() {
            var loading = document.getElementById(name+'IndividualLoading');
            loading.style.display = 'block'; // show loading button
            var option = this.options[this.selectedIndex].value;
            var user = this.name;
            $.ajax({
                url: '/ajax/entityChangeUser',
                type: "POST",
                dataType: "json",
                data: {
                    "user": user,
                    "role": option,
                    "entity": entityId
                },
                async: true,
                success: function (data) {
                    loading.style.display = 'none'; // hide loading
                    var username = document.getElementById('modalShareUser'+user);
                    if(data.enabled === true)
                        username.className = 'userName';
                    else
                        username.className = 'userName text-secondary';
                }
            });
        }
        function ajaxBoardRemoveUser() {
            var loading = document.getElementById(name+'IndividualLoading');
            loading.style.display = 'block'; // show loading button
            var element = this;
            $('.userRemove').tooltip('dispose');
            $.ajax({
                url: '/ajax/entityRemoveUser',
                type: "POST",
                dataType: "json",
                data: {
                    "user": element.name,
                    "entity": entityId
                },
                async: true,
                success: function (data) {
                    loading.style.display = 'none'; // hide loading
                    if(data.success === true) {
                        document.getElementById(name+'Userlist').innerHTML = data.result;
                        initView();
                    }
                }
            });
        }
        // Add username to share - autocomplete
        // uses bootstrap jquery library
        // https://www.jqueryscript.net/form/jQuery-Bootstrap-4-Typeahead-Plugin.html
        $('#'+name+'AddUser').typeahead({
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
                        document.getElementById(name+"InviteBtn").style.display = 'block';
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
                if($('#'+name+'AddUser').is(':focus')){
                    ajaxInviteUser();
                }
            }
        };
        var inviteBtn = document.getElementById(name+'InviteBtn');
        if(inviteBtn !== null)
            inviteBtn.onclick = function () {ajaxInviteUser()};
        function ajaxInviteUser () {
            var username = document.getElementById(name+'AddUser');
            // regex to match either email address, or my user from db (name@ ... )
            var re = /^.+\(.{2,}@ \.\.\. \)$|^.{2,}@[a-z0-9]{2,}\.[a-z0-9]+$/i;
            if(username.value.match(re)) {
                var loading = document.getElementById(name+'InviteLoading');
                loading.style.display = 'block'; // show loading button
                username.className = 'form-control';
                var roleSelect = document.getElementById(name+'InviteRole');
                var option = roleSelect.options[roleSelect.selectedIndex].value;
                $.ajax({
                    url: '/ajax/entityInviteUser',
                    type: "POST",
                    dataType: "json",
                    data: {
                        "username": username.value,
                        "entity": entityId,
                        "role": option
                    },
                    async: true,
                    success: function (data) {
                        document.getElementById(name+"InviteBtn").style.display = 'none';
                        username.value = ''; // reset the input
                        loading.style.display = 'none'; // hide loading
                        var tip = $('#'+name+'AddUser');
                        tip.tooltip('show'); // show 'user invited' msg
                        setTimeout(function(){
                            tip.tooltip('dispose');
                        }, 3500);
                    }
                });
            }
            else {
                username.className = 'form-control is-invalid';
            }
        }
    }
    var boardId = document.getElementById('boardId');
    if(boardId !== null) { // its loaded on Board overview, initiate Board sharing
        // init sharing modal for whole board
        initEntitySharing('modalBoardShare', boardId.value);
        // On modal window open focus the share link text
        $("#modalShareBoard").on('shown.bs.modal', function(){
            var copyText = document.getElementById("modalBoardShareLink");
            copyText.select();
            // initView();
        });
    }
});