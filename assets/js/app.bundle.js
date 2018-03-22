
$(document).ready(function() {

    var navbarUsername = document.getElementById('navbar-username');
    var navbarUserEdit = document.getElementById('navbar-user-edit');
    $('#navbar-user-menu').bind('click', function (e) { e.stopPropagation() });
    navbarUserEdit.onclick = function() {
        navbarUsername.disabled = false;
        navbarUsername.select();
    };
    navbarUsername.onblur = function () {
        navbarUsername.disabled = true;
        var oldName = document.getElementById('user-name');
        if(oldName.value !== navbarUsername.value) // if name was changed
            ajaxChangeUsername(navbarUsername.value);
    };
    navbarUsername.onkeypress = function (e) {
        e = e || window.event;
        if (e.keyCode === 13) { // Enter
            ajaxChangeUsername(navbarUsername.value);
        }
    };
    function ajaxChangeUsername(name) {
        if(name.length > 0) {
            navbarUserEdit.innerHTML = '<i class="fas fa-1x fa-spinner"></i>';
            $.ajax({
                url:'/ajax/userNameChange',
                type: "POST",
                dataType: "json",
                data: {
                    "name": name
                },
                async: true,
                success: function (data) {
                    navbarUserEdit.innerHTML = '<i class="fas fa-1x fa-edit"></i>';
                    navbarUsername.disabled = true;
                    document.getElementById('user-name').value = data.name;
                }
            });
        }
    }
});