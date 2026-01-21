import layoutActivation from './vendor';

import '../../bootstrap/dist/modal';
import '../../bootstrap/dist/tooltip';

/**
 * Refresh layout
 */
export default function (Routing, form, modal, event) {

    let body = $('body');
    let formID = form.attr('id');
    let formDataEl = document.getElementById(formID);

    if (formDataEl) {

        let formData = new FormData(formDataEl);
        let action = form.attr('action');
        let loader = body.find('#layout-preloader');
        let scrollElement = form.data('scroll-to');

        if (modal) {
            body.find('#' + modal.attr('id')).remove();
            body.removeClass('modal-open').removeAttr('style');
            $('.modal-backdrop').remove();
        }

        $.ajax({
            url: action.indexOf('?') > -1 ? action + '&ajax=true' : action + '?ajax=true',
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            async: true,
            beforeSend: function () {
                loader.toggleClass('d-none');
            },
            success: function () {
                let url = window.location.href;
                $.ajax({
                    url: url.indexOf('?') > -1 ? url + '&ajax=true' : url + '?ajax=true',
                    type: "GET",
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    async: true,
                    beforeSend: function () {
                    },
                    success: function (response) {
                        let html = $(response.html).find("#layout-grid")[0];
                        $("#layout-grid").replaceWith(html);
                        if (typeof $(scrollElement) != "undefined" && $(scrollElement).length > 0) {
                            $("body, html").animate({
                                scrollTop: $(scrollElement).offset().top
                            }, 'slow');
                        }
                        layoutActivation(Routing);
                        $('[data-bs-toggle=tooltip]').tooltip({trigger: "hover"});
                        loader.toggleClass('d-none');
                        let popupImages = document.querySelectorAll('.glightbox')
                        if (popupImages.length > 0) {
                            import('../../../vendor/plugins/popup').then(({default: popup}) => {
                                new popup();
                            }).catch(error => console.error(error.message));
                        }
                    },
                    error: function (errors) {
                        /** Display errors */
                        import('../../core/errors').then(({default: displayErrors}) => {
                            new displayErrors(errors);
                        }).catch(error => console.error(error.message));
                    }
                });
            },
            error: function (errors) {
                /** Display errors */
                import('../../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            }
        });
    }

    event.stopImmediatePropagation();
    return false;
}