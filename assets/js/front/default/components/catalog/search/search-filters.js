import refresh from "./refresh";

export default function() {

    let body = $('body');
    let selects = body.find('#search-filter-form .select-search');
    let loader = body.find('#scroll-wrapper .loader-wrapper');

    selects.each(function() {

        let select = $(this);
        select.select2();

        select.on("change", function(e) {

            if(!$('body').hasClass('text-search-process')) {

                let el = $(this);
                let form = el.closest('form');
                let action = form.attr('action') + "?" + form.serialize() + '&ajax=true';

                $('#search_products_text').val('');

                $.ajax({
                    url: action,
                    type: "GET",
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    async: false,
                    beforeSend: function () {
                        loader.removeClass('d-none');
                    },
                    success: function (response) {
                        let uriFormTag = decodeURI(form.serialize()).replace(/filters_products\[/g,'');
                        let uri = '?' + uriFormTag.replace(/]/g,'');
                        refresh(body, uri, response, loader);
                    },
                    error: function () {}
                });

                e.stopImmediatePropagation();
                return false;
            }
        });
    });
}