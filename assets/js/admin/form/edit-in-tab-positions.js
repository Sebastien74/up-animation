/**
 * Set positions
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (items) {

    let body = $('body');
    let loader = body.find('#entity-preloader');

    loader.removeClass('d-none');
    let ArrayOfDate = [];
    let firstItem = $(items)[0];

    if (firstItem) {

        let pathAjax = $(firstItem).data('pos-path');
        items.each(function (i, el) {
            let newPosition = i + 1;
            let elementId = $(el).data('id');
            let inputPosition = $(el).find('.input-position');
            inputPosition.val(newPosition);
            $('#' + elementId).attr('data-position', newPosition);
            ArrayOfDate.push({
                'id': elementId,
                'position': newPosition
            });
        });

        $.ajax({
            url: pathAjax,
            type: "POST",
            dataType: 'json',
            data: {
                'data': JSON.stringify(ArrayOfDate)
            },
            async: true,
            beforeSend: function () {
            },
            success: function () {
                loader.addClass('d-none');
            },
            error: function (errors) {
                /** Display errors */
                import('../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            }
        });
    }
}