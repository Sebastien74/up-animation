$('body .tree-list').on('click', '.caret', function () {

    let child = $(this).closest('li.item').find('.nested').first();

    if (child.hasClass('active')) {
        child.removeClass('active');
    } else {
        child.addClass('active');
    }
});