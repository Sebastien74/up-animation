import select2 from "../../vendor/plugins/select2";
import ajaxRowProcess from "./ajax-row";

/**
 * Search in index
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let body = $('body');

    body.on('keydown', 'input#index_search_search', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#index-search-submit').trigger('click');
        }
    });

    body.on('click', '#index-search-submit', function (e) {

        let loader = $('body').find('#index-preloader');
        loader.toggleClass('d-none');

        let el = $(this);
        let input = el.closest('.form-group').find('input');
        let value = input.val();
        let form = input.closest('form');
        let formId = form.attr('id');
        let uri = location.pathname.substr(1) + "?index_search[search]=" + value;
        let formData = new FormData(document.getElementById(formId));

        $.ajax({
            url: '/' + uri + "&ajax=true",
            type: "GET",
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            async: true,
            beforeSend: function () {
                history.replaceState("", "", '/' + uri);
            },
            success: function (response) {

                let body = $('body');
                let html = $(response.html).find("#result")[0];
                let ajaxContent = body.find("#result");
                ajaxContent.replaceWith(html);

                body.find('#index-preloader').removeClass('d-none');

                let showBtnDelete = body.find('#index-delete-show');
                if (showBtnDelete.hasClass('d-none')) {
                    showBtnDelete.removeClass('d-none');
                }

                let removeBtn = body.find('#index-delete-submit');
                if (!removeBtn.hasClass('d-none')) {
                    removeBtn.addClass('d-none');
                }

                let pagination = body.find('#entities-index .pagination .page-link');

                pagination.each(function () {
                    let link = $(this);
                    let href = link.attr('href');
                    if (typeof href != "undefined") {
                        let newHref = href.replace('&ajax=true', '');
                        link.attr('href', newHref);
                    }
                });

                body.find('#index-preloader').addClass('d-none');

                select2();
                ajaxRowProcess();
            },
            error: function (error) {
            }
        });

        e.stopImmediatePropagation();
        return false;
    });
}