import '../../../scss/admin/pages/icons-library.scss';
import route from "../../vendor/components/routing";

let body = $('body');

/** To add Icon */
body.on('click', '.icon-add', function (e) {

    e.preventDefault();

    let el = $(this);
    let container = el.closest('.icon-wrap');
    let status = el.attr('data-status') === 'true' ? 1 : 0;
    let newStatus = status ? 'false' : 'true';
    let routeName = status ? 'admin_icon_remove' : 'admin_icon_add';

    container.toggleClass('active');

    $.ajax({
        url: route(routeName, {
            website: body.data('id'),
            path: JSON.stringify(el.data('path'))
        }),
        type: "GET",
        processData: false,
        contentType: false,
        dataType: 'json',
        beforeSend: function () {
        },
        success: function () {

            if (newStatus === 'true') {
                el.attr('data-original-title', el.data('remove-txt')).parent().find('.tooltip-inner').html(el.data('add-txt'));
            } else {
                el.attr('data-original-title', el.data('add-txt')).parent().find('.tooltip-inner').html(el.data('remove-txt'));
            }

            el.attr('data-status', newStatus);
            el.find('svg').toggleClass('d-none');
            if (newStatus === 'true' && !el.hasClass('active')) {
                el.addClass('active');
            } else {
                el.removeClass('active');
            }
        },
        error: function (errors) {
            /** Display errors */
            import('../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
        }
    });

    e.stopImmediatePropagation();
    return false;
});

/** To copy icon class */
body.on('click', '.icon-copy', function () {
    let el = $(this);
    let text = el.closest('.icon-wrap').find('img');
    let iconPath = text.attr('src');
    copyText(iconPath);
});

/** To copy text */
function copyText(text) {
    let $temp = $("<input>");
    $("body").append($temp);
    $temp.val(text).select();
    document.execCommand("copy");
    $temp.remove();
}

/** Icons search */
$(".icons-search input").keyup(function () {

    let filter = $(this).val();

    $("#icons-contents").find(".search-icon").each(function () {

        let el = $(this);
        let icon = el.text();

        if (icon.indexOf(filter) != -1) {
            el.closest('.item').fadeIn();
        } else {
            el.closest('.item').fadeOut();
        }
    });
});