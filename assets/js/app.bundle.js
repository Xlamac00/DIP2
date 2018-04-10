
$(document).ready(function() {

    /** **************************************************************** **
     *  ****************        NAVBAR - USERNAME        ***************
     ** **************************************************************** **/
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
            navbarUserEdit.innerHTML = '<i class="fas fa-1x fa-spinner fa-spin"></i>';
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
    /** **************************************************************** **
     *  **************         NAVBAR - MY PROJECTS        *************
     ** **************************************************************** **/
    var navbarProjectBtn = document.getElementById('navbarProjectBtn');
    if(navbarProjectBtn !== null) {
        navbarProjectBtn.onclick = function () {
            var navbarProjectsBody = document.getElementById('navbarProjectsBody');
            if (navbarProjectsBody.innerHTML.length === 0) { // My Projects body is empty - download it
                navbarProjectsBody.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                $.ajax({
                    url: '/ajax/getBoardFavorite',
                    type: "POST",
                    dataType: "json",
                    data: {},
                    async: true,
                    success: function (data) {
                        navbarProjectsBody.innerHTML = data.render;
                    }
                });
            }
        };
        var name = document.getElementById('modalNewBoardName');
        var colors = document.getElementById('modalNewBoardColors');
        $('#modalNewBoard').on('shown.bs.modal', function () {
            setHeaderBackground(colors);
            name.focus();
        });
        $('.radio-container').click(function () {
            setHeaderBackground(this);
        });
        function setHeaderBackground(container) {
            var color = $('input[name=color]:checked', container).val();
            if(color !== null && color !== undefined) {
                name.classList.remove('is-invalid');
                var header = document.getElementById('modalNewBoardHeader');
                header.className = 'modal-header text-light bg-'+color;
                name.focus();
            }
        }
        var createBtn = document.getElementById('modalNewBoardCreateBtn');
        createBtn.onmousedown = function() {
            if(name.value.length <= 0) {
                name.classList.add('is-invalid');
                name.focus();
            }
            else {
                name.classList.remove('is-invalid');
                ajaxNewBoard();
            }
        };
        function ajaxNewBoard() {
            var loading = document.getElementById('modalNewBoardHeader');
            loading.innerHTML =  '<i class="fa fa-2x fa-spinner fa-spin">';
            var color = $('input[name=color]:checked', colors).val();
            $.ajax({
                url: '/ajax/boardNew',
                type: "POST",
                dataType: "json",
                data: {
                    "name": name.value,
                    "color": color
                },
                async: true,
                success: function (data) {
                    location.href = data.link;
                }
            });
        }
    }

    /** **************************************************************** **
     *  ************      DASHBOARD & PROJECT - USERLIST     ***********
     ** **************************************************************** **/
    // On modal window open download content
    $('#modalUserlist').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var name = button.data('name');
        var id = button.data('id');
        $.ajax({
            url: '/ajax/entityGetActiveUserlist',
            type: "POST",
            dataType: "json",
            data: { "name": name, "entity": id },
            async: true,
            success: function (data) {
                var modal = document.getElementById('modalUserlist');
                modal.innerHTML = data.render;
            }
        });
    });
});