import refresh from "./refresh";

export default function() {

    let body = $('body');
    let loader = body.find('#scroll-wrapper .loader-wrapper');

    body.on('keyup keypress', '#search-text-form', function(e) {
        let keyCode = e.keyCode || e.which;
        if (keyCode === 13) {
            e.stopImmediatePropagation();
            if(!body.hasClass('text-search-process')) {
                sendRequest(e, $(this));
            }
            return false;
        }
    });

    body.on('click', '#search-product-text-submit', function (e) {
        let el = $(this);
        let form = el.closest('form');
        sendRequest(e, form);
    });

    function sendRequest(e, form) {

        e.preventDefault();

        let selects = body.find('#search-filter-form .select-search');
        let action = form.attr('action') + "?" + form.serialize() + '&ajax=true';
        let term = form.find('.form-control').val();

        $.ajax({
            url: action,
            type: "GET",
            processData: false,
            contentType: false,
            dataType: 'json',
            async: true,
            beforeSend: function () {
                loader.removeClass('d-none');
                body.addClass('text-search-process');
                selects.val(null).trigger("change");
            },
            success: function (response) {
                let uri = decodeURI(form.serialize()) === "search_products[text]=" ? null : "?" + decodeURI(form.serialize()).replace("search_products[text]", "text");
                refresh(body, uri, response, loader, term);
            },
            complete: function () {
                body.removeClass('text-search-process');
            },
            error: function () {}
        });

        e.stopImmediatePropagation();
        return false;
    }
}