import resetModal from "../../vendor/components/reset-modal";
import select2 from '../../vendor/plugins/select2'

/**
 * Duplicate form
 */
export default function () {

    /** Show duplicate modal */
    $('body').on('click', '.duplicate-btn', function (e) {

        let btn = $(this);
        let body = $('body');
        let loader = body.find('#main-preloader');
        let loaderData = btn.data('preloader');
        if (typeof loaderData != 'undefined') {
            loader = $(loaderData);
        }

        let path = btn.data('path');
        let url = path + '?ajax=true';
        if (path.indexOf('?') > -1) {
            url = path + '&ajax=true'
        }

        $.ajax({
            url: url,
            type: "GET",
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
                loader.toggleClass('d-none');
            },
            success: function (response) {

                if (response.redirection) {
                    window.location.href = response.redirection;
                    return;
                }

                let html = response.html;
                let modal = $(html).find('.modal');
                let container = $('body');

                container.append(response.html);

                let modalEl = container.find('#' + modal.attr('id'));

                modalEl.modal('show');
                loader.toggleClass('d-none');

                select2();
                import('./ajax').then(({default: ajaxForm}) => {
                    new ajaxForm();
                }).catch(error => console.error(error.message));

                modalEl.on("hide.bs.modal", function () {
                    resetModal(modalEl, true);
                    $('.modal-wrapper').remove();
                });
            },
            error: function (errors) {

                let body = $('body');
                let modal = body.find('.modal');

                /** Display errors */
                import('../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));

                resetModal(modal, true);
            }
        });

        e.stopImmediatePropagation();
        return false;
    });
}