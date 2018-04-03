
$(document).ready(function() {

    // On modal window open initiate all the buttons[[[[
    $(".modalShareIssue").on('shown.bs.modal', function(){
        var modalId = this.id;
        var issueId = document.getElementById(modalId+'id').value;
        // focuses the share link on modal open window
        var copyText = document.getElementById(modalId+"Link");
        copyText.select();

        // Copy link to clipboard button
        var copyBtn = document.getElementById(modalId+"CopyBtn");
        copyBtn.onclick = function () {
            var link = $('#'+modalId+'Link'); // show text it was copied and hide it after a while
            link.tooltip('show');
            setTimeout(function(){
                link.tooltip('dispose');
            }, 1000);
            copyText.select();
            document.execCommand("Copy");
        };

        //
        var disableBtn = document.getElementById(modalId+'Disable');
        if(disableBtn !== null)
            disableBtn.onclick = function () {ajaxIssueChangeShare(false);};
        var enableBtn = document.getElementById(modalId+'Enable');
        enableBtn.onclick = function () {ajaxIssueChangeShare(true);};
        function ajaxIssueChangeShare(newValue) {
            $.ajax({
                url: '/ajax/issueChangeShare',
                type: "POST",
                dataType: "json",
                data: {
                    "enable": newValue,
                    "issue": issueId
                },
                async: true,
                success: function (data) {
                    var eSection = document.getElementById(modalId+'EnabledSection');
                    var dSection = document.getElementById(modalId+'DisabledSection');
                    if(data.enable === true) { // show section with link sharing option
                        eSection.style.display = 'block';
                        dSection.style.display = 'none';
                        copyText.select(); //select link text
                        document.getElementById(modalId+"Options").style.display = 'block';
                    }
                    else { // show section with link-sharing enable button
                        eSection.style.display = 'none';
                        dSection.style.display = 'block';
                        document.getElementById(modalId+"Options").style.display = 'none';
                    }
                }
            });
        }

        // Disable the option to edit by anonymous users if its for read only
        var roleSelect = document.getElementById(modalId+'Select');
        if(roleSelect !== null)
            roleSelect.onchange = function () {ajaxIssueChangeRights()};
        var anonCheck = document.getElementById(modalId+'Anonymous');
        if(anonCheck !== null)
            anonCheck.onchange = function () {ajaxIssueChangeRights()};
        function ajaxIssueChangeRights() {
            var option = roleSelect.options[roleSelect.selectedIndex].value;
            var anonymous = document.getElementById(modalId+'Anonymous');
            var loading = document.getElementById(modalId+'Options');
            var loadingOld = loading.innerHTML;
            loading.innerHTML =  '<i class="fa fa-spinner fa-spin">';
            $.ajax({
                url: '/ajax/issueChangeShareRights',
                type: "POST",
                dataType: "json",
                data: {
                    "option": option,
                    "anonymous": anonymous.checked,
                    "issue": issueId
                },
                async: true,
                success: function (data) {
                    var text = document.getElementById(modalId+'AnonymousText');
                    if(data.option !== false) { // if all went right
                        if (data.option === 'ROLE_ISSUE_READ') {
                            anonymous.disabled = true;
                            text.style.color = '#999';
                        }
                        else {
                            anonymous.disabled = false;
                            text.style.color = '#000';
                        }
                        anonymous.checked = (data.option === 'ROLE_ISSUE_ANONWRITE');
                    }
                    loading.innerHTML = loadingOld;
                }
            });
        }

        // toggle view in modal window - change between link sharing and individual user management
        var individualBtn = document.getElementById(modalId+'Individual');
        if(individualBtn !== null) {
            individualBtn.onclick = function () {
                if (document.getElementById(modalId + "IndividualSection").style.display === 'none') {
                    $.ajax({
                        url: '/ajax/issueGetUserlist',
                        type: "POST",
                        dataType: "json",
                        data: {
                            "issue": issueId
                        },
                        async: true,
                        success: function (data) {
                            document.getElementById(modalId+'Userlist').innerHTML = data.result;
                            // initView();
                            document.getElementById(modalId + "PrimarySection").style.display = 'none';
                            document.getElementById(modalId + "IndividualSection").style.display = 'block';
                            individualBtn.innerHTML = 'Return';
                        }
                    });
                }
                else {
                    document.getElementById(modalId + "PrimarySection").style.display = 'block';
                    document.getElementById(modalId + "IndividualSection").style.display = 'none';
                    individualBtn.innerHTML = 'Manage individual users';
                }
            };
        }

        $('#'+modalId+'AddUser').typeahead({
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
                        document.getElementById(modalId+"InviteBtn").style.display = 'block';
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
                if($('#'+modalId+'AddUser').is(':focus')){
                    ajaxInviteUser();
                }
            }
        };

        var inviteBtn = document.getElementById(modalId+'InviteBtn');
        if(inviteBtn !== null)
            inviteBtn.onclick = function () {ajaxInviteUser()};
        function ajaxInviteUser () {
            var name = document.getElementById(modalId+'AddUser');
            // regex to match either email address, or my user from db (name@ ... )
            var re = /^.+\(.{2,}@ \.\.\. \)$|^.{2,}@[a-z0-9]{2,}\.[a-z0-9]+$/i;
            if(name.value.match(re)) {
                var loading = document.getElementById(modalId+'InviteLoading');
                loading.style.display = 'block'; // show loading button
                name.className = 'form-control';
                var roleSelect = document.getElementById(modalId+'InviteRole');
                var option = roleSelect.options[roleSelect.selectedIndex].value;
                $.ajax({
                    url: '/ajax/issueInviteUser',
                    type: "POST",
                    dataType: "json",
                    data: {
                        "username": name.value,
                        "issue": issueId,
                        "role": option
                    },
                    async: true,
                    success: function (data) {
                        console.log(data);
                        document.getElementById(modalId+"InviteBtn").style.display = 'none';
                        name.value = ''; // reset the input
                        loading.style.display = 'none'; // hide loading
                        var tip = $('#'+modalId+'AddUser');
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
        }
    });
});