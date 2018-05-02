
$(document).ready(function() {
    var tips = document.getElementById('tipsNames').value.split(',');
    var container = document.getElementById('mainContainer');

    $('.tipsHideForever').click(function () {
        $.ajax({
            url: '/ajax/userHideAllTips',
            type: "POST",
            dataType: "json",
            data: {
                "board": name
            },
            async: true
        });
        console.log('forever');
    });

    if(tips.length > 0) { // show first tip
        var tip = tips[0];
        document.getElementById('tips-overlay').style.display = 'block';
        document.getElementById(tip+'TipBig').classList.add('d-md-block');
        document.getElementById(tip+'TipSmall').classList.remove('d-none');
        document.getElementById(tip+'TipSmall').classList.add('d-sm-block');
        document.getElementById(tip+'TipSmall').classList.add('d-md-none');
        container.onclick = function () {
            hideAllTips(tip);
            container.onclick = null;
        }
    }

    function hideAllTips(element) {
        document.getElementById('tips-overlay').style.display = 'none';
        document.getElementById(element+'TipBig').classList.remove('d-md-block');
        document.getElementById(element+'TipSmall').classList.add('d-none');
        document.getElementById(element+'TipSmall').classList.remove('d-sm-block');
        document.getElementById(element+'TipSmall').classList.remove('d-md-none');
    }


    /** ******************************************************************
     *  TIP: CREATE NEW BOARD
     *  *****************************************************************/
    var createNewBoardBigBtn = document.getElementById('createNewBoardBtnBig');
    if(createNewBoardBigBtn !== null)
        createNewBoardBigBtn.onclick = function() {createNewBoardFocus()};
    var createNewBoardSmallBtn = document.getElementById('createNewBoardBtnSmall');
    if(createNewBoardSmallBtn !== null)
        createNewBoardSmallBtn.onclick = function() {createNewBoardFocus()};
    function createNewBoardFocus() {
        hideAllTips('createNewBoard');
        document.getElementById('dashboardBoardName').focus();
    }

});