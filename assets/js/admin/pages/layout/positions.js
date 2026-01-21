import serialize from "../../../vendor/components/serialize";
import route from "../../core/routing";
import '../../bootstrap/dist/tooltip';

/**
 * Set positions
 */
export default function (Routing, items, routeName, block = false) {

    let body = $('body');
    let loader = body.find('#layout-preloader');

    loader.removeClass('d-none');
    $('[data-bs-toggle="tooltip"]').tooltip('hide');

    let data = {};
    items.each(function (i, el) {
        let newPosition = i + 1;
        let elementId = $(el).attr('id');
        let id = $(el).data('id');
        $('#' + elementId).attr('data-position', newPosition);
        if (block) {
            data[id] = [$(el).closest('.column').data('id'), newPosition];
        } else {
            data[id] = newPosition;
        }
    });

    if (!$.isEmptyObject(data)) {
        $.ajax({
            url: route(Routing, routeName, {website: body.data('id'), data: serialize(data)}) + "?ajax=true",
            type: "POST",
            processData: false,
            contentType: false,
            dataType: 'json',
            async: true,
            beforeSend: function () {
            },
            success: function (response) {
                loader.addClass('d-none');
            },
            error: function (errors) {
                /** Display errors */
                import('../../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            }
        });
    }
}