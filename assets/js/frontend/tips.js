
$(document).ready(function() {

    var start = document.getElementById('startTips');
    if(start !== null && start.value.length > 0) { // show first tip
        showTip(start.value);
    }

    // listener if showTip should be called from other JS file
    document.addEventListener('showTip', function (e) {
        if(e.detail.element !== null) {
            e.preventDefault();
            showTip(e.detail.element);
        }
    }, false);

    function showTip(element) {
        var size_sm, size_bg;
        if(element === 'createNewIssue' || element === "createNewBoard") { // size where switch between tips
            size_sm = 'sm';
            size_bg = 'md';
        }
        else {
            size_sm = 'md';
            size_bg = 'lg';
        }
        document.getElementById('tips-overlay').style.display = 'block';
        document.getElementById(element+'TipBig').classList.add('d-'+size_bg+'-block');
        var small = document.getElementById(element+'TipSmall');
        if(small !== null) {
            small.classList.remove('d-none');
            small.classList.add('d-'+size_sm+'-block');
            small.classList.add('d-'+size_bg+'-none');
        }
        var container = document.getElementById('mainContainer');
        container.onclick = function (e) {
            if(e.target.id === 'tips-overlay' || e.target.classList.contains('tips-check')) {
                $.ajax({
                    url: '/ajax/userHideOneTip',
                    type: "POST",
                    dataType: "json",
                    data: {
                        "tip": element
                    },
                    async: true
                });
                hideAllTips(element);
                container.onclick = null;
            }
        };
        $('.tipsHideForever').click(function () {
            $.ajax({
                url: '/ajax/userHideAllTips',
                type: "POST",
                dataType: "json",
                data: {},
                async: true
            });
            hideAllTips(element);
            container.onclick = null;
        });
    }

    function hideAllTips(element) {
        var big = document.getElementById(element+'TipBig');
        if(big !== null) {
            document.getElementById('tips-overlay').style.display = 'none';
            big.parentNode.removeChild(big);
            var small = document.getElementById(element+'TipSmall');
            if(small !== null) {
                small.parentNode.removeChild(small);
            }
            if(element === 'createNewBoard')
                document.getElementById('dashboardBoardName').focus();
            else if(element === 'createNewIssue')
                document.getElementById('dashboardIssueName').focus();
            else if(element === 'createNewTask')
                document.getElementById('gaugeAddNewName').focus();
            else if(element === 'makeComment')
                document.getElementById('gaugeCommentText').focus();
        }
    }
});