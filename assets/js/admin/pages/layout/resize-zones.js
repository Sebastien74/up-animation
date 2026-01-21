import route from "../../core/routing";

/**
 * Zones resize
 */
export default function (Routing) {

    $('body').on('click', '.zone-resize', function handler(e) {

        e.preventDefault();

        let el = $(this);
        let body = $('body');
        let website = body.data('id');
        let titleBlock = el.parent();
        let iconWrap = el.find('.icon-wrap');
        let zone = el.attr('data-zone');
        let newSize = el.attr('data-size') === 'true' ? 0 : 1;
        let size = newSize === 1 ? 'true' : 'false';
        let loader = body.find('#layout-preloader');

        $.ajax({
            url: route(Routing, 'admin_zone_size', {website: website, zone: zone, size: newSize}) + "&ajax=true",
            type: "GET",
            processData: false,
            contentType: false,
            dataType: 'json',
            async: true,
            beforeSend: function () {
                loader.toggleClass('d-none');
                el.attr('data-size', size);
            },
            success: function () {
                if (size == 'false') {
                    titleBlock.attr('data-original-title', el.data('compress')).parent().find('.tooltip-inner').html(el.data('compress'));
                } else {
                    titleBlock.attr('data-original-title', el.data('expand')).parent().find('.tooltip-inner').html(el.data('expand'));
                }
                iconWrap.toggleClass('d-none');
                loader.toggleClass('d-none');
            },
            error: function (errors) {
                /** Display errors */
                import('../../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            }
        });

        e.stopImmediatePropagation();
        return false;
    });
}