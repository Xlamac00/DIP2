
$(document).ready(function() {
    var newBoardName = document.getElementById('dashboardBoardName');
    var dashboardColors = document.getElementById('dashboardBoardColors');
    var headerSpace = document.getElementById('dashboardHeaderSpace');
    var buttons = document.getElementById('dashboardBoardButtons');
    var dashboardHeader = document.getElementById('dashboardBoardHeader');
    var dashboardCreateBtn = document.getElementById('dashboardBoardCreateBtn');
    var dashboardCloseBtn = document.getElementById('dashboardBoardNameClose');
    var createBtnClicked = false;
    var createBtnError = false;
    if(newBoardName !== null) {
        newBoardName.onfocus = function () {
            newBoardName.classList.remove("borderless");
            buttons.className = 'mt-3 d-block';
            dashboardColors.className = "d-block";
            headerSpace.className = "pt-2";
            var color = $('input[name=radio]:checked', dashboardColors).val();
            removeBackground(dashboardHeader);
            dashboardHeader.classList.add('bg-' + color);
        };
        newBoardName.onblur = function (event) {
            var eventClass = event.explicitOriginalTarget;
            if (eventClass !== null && eventClass.classList[0] !== 'checkmark' && createBtnClicked === false) {
                hideDashboardBoardName();
            }
        };
        newBoardName.onkeypress = function (e) {
            e = e || window.event;
            if (e.keyCode === 13) { // Enter
                dashboardBoardCreateConfirm();
            }
        };
        dashboardColors.onclick = function () {
            var color = $('input[name=radio]:checked', dashboardColors).val();
            removeBackground(dashboardHeader);
            newBoardName.classList.remove('is-invalid');
            dashboardHeader.classList.add('bg-' + color);
            newBoardName.focus();
        };
        function hideDashboardBoardName() {
            newBoardName.classList.remove('is-invalid');
            newBoardName.classList.add("borderless");
            buttons.className = 'mt-3 d-none';
            dashboardColors.className = "d-none";
            headerSpace.className = "pt-5";
            if (newBoardName.value.length <= 0) {
                removeBackground(dashboardHeader);
                dashboardHeader.classList.add('bg-light');
            }
        }
        dashboardCreateBtn.onmousedown = function () {
            createBtnClicked = true;
            dashboardBoardCreateConfirm();
        };
        function dashboardBoardCreateConfirm() {
            if (newBoardName.value.length <= 0) {
                newBoardName.classList.add('is-invalid');
                createBtnError = true;
                newBoardName.focus();
            }
            else {
                ajaxNewBoard();
                createBtnError = false;
                newBoardName.value = '';
                newBoardName.classList.remove('is-invalid');
                hideDashboardBoardName();
            }
        }
        dashboardCreateBtn.onmouseup = function () {
            if (createBtnError === false) {
                createBtnClicked = false;
                hideDashboardBoardName();
            }
        };
        dashboardCloseBtn.onclick = function () {
            createBtnClicked = false;
            newBoardName.value = '';
            hideDashboardBoardName();
        };
        function ajaxNewBoard() {
            var loading = document.getElementById('dashboardBoardHeader');
            loading.innerHTML = '<i class="fa fa-2x fa-spinner fa-spin">';
            var color = $('input[name=radio]:checked', dashboardColors).val();
            $.ajax({
                url: '/ajax/boardNew',
                type: "POST",
                dataType: "json",
                data: {
                    "name": newBoardName.value,
                    "color": color
                },
                async: true,
                success: function (data) {
                    location.href = data.link;
                }
            });
        }
    }
    function removeBackground(element) {
        for (var i = 0; i < element.classList.length; i++) {
            var _class = element.classList[i];
            if ((_class[0] + _class[1] + _class[2]) === 'bg-')
                element.classList.remove(_class);
        }
    }

    $('.dashboardFavorite').click(ajaxMakeFavorite);
    function ajaxMakeFavorite(ev) {
        var curName = ev.currentTarget.id;
        var name = curName.substr(0, 6) === 'favbtn' ? curName.substr(6) : curName.substr(3);
        var loading = document.getElementById('btn'+name);
        loading.innerHTML =  '<i class="fa fa-spinner fa-spin">';
        $.ajax({
            url: '/ajax/boardFavorite',
            type: "POST",
            dataType: "json",
            data: {
                "board": name
            },
            async: true,
            success: function (data) {
                var favoriteSection = document.getElementById('dashboardFavoriteSection');
                var favBtn = document.getElementById('btn'+data.board);
                // if board is not favorite (it wasnt), add it to the dom
                if(data.isFavorite === true) {
                    favoriteSection.className = 'd-block';
                    var favoriteDeck = document.getElementById('dashboardFavoriteDeck');
                    favoriteDeck.innerHTML =  favoriteDeck.innerHTML + data.render;
                    $('.favorite').click(ajaxMakeFavorite);
                    favBtn.innerHTML = '<i class="fas fa-star"></i>';
                    favBtn.blur();
                }
                else { // remove item from the dom, potentially hide all section
                    var favItems = favoriteSection.children[1].children;
                    for (var i = 0; i < favItems.length; i++) {
                        if(favItems[i].id === 'fav'+data.board)
                            favItems[i].remove();
                    }
                    if(favItems.length === 0)
                        favoriteSection.className = 'd-none';
                    favBtn.innerHTML = '<i class="far fa-star"></i>';
                    favBtn.blur();
                }
            }
        });
    }
});