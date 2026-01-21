import route from "../../core/routing";

/**
 * Standardize Block[] width in Col
 */
export default function (Routing) {

    $('body').on('click', '.col-blocks-standardize', function handler(e) {

        e.preventDefault();

        let el = $(this);
        let body = $('body');
        let website = body.data('id');
        let titleBlock = el.parent();
        let iconWrap = el.find('.icon-wrap');
        let col = el.attr('data-col');
        let newStandardize = el.attr('data-standardize') === 'true' ? 0 : 1;
        let standardize = newStandardize === 1 ? 'true' : 'false';
        let loader = body.find('#layout-preloader');

        $.ajax({
            url: route(Routing, 'admin_blocks_standardize', {website: website, col: col, standardize: newStandardize}),
            type: "GET",
            processData: false,
            contentType: false,
            dataType: 'json',
            async: true,
            beforeSend: function () {
                loader.toggleClass('d-none');
                el.attr('data-standardize', standardize);
            },
            success: function () {
                if (standardize == 'false') {
                    titleBlock.attr('data-original-title', el.data('cols-standardize')).parent().find('.tooltip-inner').html(el.data('cols-default'));
                } else {
                    titleBlock.attr('data-original-title', el.data('cols-default')).parent().find('.tooltip-inner').html(el.data('cols-standardize'));
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