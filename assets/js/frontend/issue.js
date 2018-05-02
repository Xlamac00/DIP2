
$(document).ready(function() {

    // listener if showTip should be called from other JS file
    document.addEventListener('toggleAllComments', function (e) {
        e.preventDefault();
        toggleAllComments();
    }, false);

    /** Shows all comments instead of only the first 6.
     * (If there are more then 6). Displays the button to trigger this function.
     */
    function toggleAllComments() {
        // get all comments in div gaugeComments
        var commentsCount = $("#gaugeComments div.media").length;
        var showBtn = document.getElementById('gaugeCommentShowAllBtn');
        if(showBtn.getAttribute('data-field') === 'hide' && commentsCount > 5) { // make the button visible
            for(var i = 0; i < commentsCount; i++) {
                if(i > 5) // hide any more then first six
                    ($("#gaugeComments div.media")[i]).style.display = 'none';
            }
            if(commentsCount > 6)
                showBtn.style.display = 'block';
            showBtn.innerHTML = 'Show all <i class="fas fa-angle-double-down "></i>';
            showBtn.setAttribute('data-field', 'show');
        }
        else {
            for(i = 0; i < commentsCount; i++) {
                ($("#gaugeComments div.media")[i]).style.display = 'flex';
            }
            if(commentsCount <= 6)
                showBtn.style.display = 'none';
            else {
                showBtn.innerHTML = 'Hide old <i class="fas fa-angle-double-up"></i>';
                showBtn.setAttribute('data-field', 'hide');
            }
        }
        showBtn.blur();
        $('#gaugeCommentShowAllBtn').unbind('click');
        $('#gaugeCommentShowAllBtn').click(toggleAllComments);
    }
    toggleAllComments(); // call after the page loads
});