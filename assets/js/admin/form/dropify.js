import route from "../../vendor/components/routing";

/**
 * Dropify
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let trans = document.getElementById('data-translation');
    let dropifyElements = document.querySelectorAll('.dropify');

    if (dropifyElements.length === 0) {
        return;
    }

    import('dropify/dist/css/dropify.css');
    import('../../../scss/admin/lib/sweetalert.scss');

    let $dropifyElements = jQuery(dropifyElements);
    let drEvent = $dropifyElements.dropify({
        messages: {
            'default': trans.getAttribute('data-dropify-default'),
            'replace': trans.getAttribute('data-dropify-replace'),
            'remove': trans.getAttribute('data-dropify-remove'),
            'error': trans.getAttribute('data-dropify-error'),
        },
        error: {
            'fileSize': trans.getAttribute('data-dropify-file-size'),
            'minWidth': trans.getAttribute('data-dropify-min-width'),
            'maxWidth': trans.getAttribute('data-dropify-max-width'),
            'minHeight': trans.getAttribute('data-dropify-min-height'),
            'maxHeight': trans.getAttribute('data-dropify-max-height'),
            'imageFormat': trans.getAttribute('data-dropify-image-format'),
            'fileExtension': trans.getAttribute('data-dropify-file-extension')
        }
    });

    let indexPage = document.getElementById('entities-index');

    if (!indexPage) {

        drEvent.on('dropify.beforeClear', function (event, element) {

            event.result = false;

            return swal({
                title: trans.getAttribute('data-swal-delete-title'),
                text: trans.getAttribute('data-swal-delete-text'),
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: trans.getAttribute('data-swal-delete-confirm-text'),
                cancelButtonText: trans.getAttribute('data-swal-delete-cancel-text'),
                closeOnConfirm: false
            }, function () {

                let body = document.body;
                let confirmBtn = document.querySelector('.sa-button-container .confirm');
                let cancelBtn = document.querySelector('.sa-button-container .cancel');
                if (confirmBtn) confirmBtn.setAttribute('disabled', '');
                if (cancelBtn) cancelBtn.setAttribute('disabled', '');

                setTimeout(function () {

                    let input = element.input;
                    let customUrl = input.dataset.deleteUrl;
                    let screen = input.dataset.screen;
                    let media = input.dataset.media;
                    let url = route('admin_mediarelation_reset_media', {
                        "website": body.getAttribute('data-id'),
                        "mediaRelationId": input.dataset.mediaRelation,
                        "mediaClassname": input.dataset.mediaClassname,
                    });

                    if (typeof customUrl != 'undefined') {
                        url = customUrl;
                    } else if (typeof media != 'undefined') {
                        url = route('admin_media_delete', {"website": body.getAttribute('data-id'), "media": media});
                    }

                    let ajaxUrl = url + (url.indexOf('?') > -1 ? "&ajax=true" : "?ajax=true");

                    fetch(ajaxUrl, {
                        method: "DELETE"
                    })
                        .then(response => response.json())
                        .then(response => {
                            if (response.success) {
                                element.resetFile();
                                input.value = '';
                                element.resetPreview();
                                input.dispatchEvent(new CustomEvent("dropify.afterClear", { detail: [this] }));
                            }
                            swal.close();
                            if (typeof media != 'undefined' && typeof screen == 'undefined') {
                                location.reload();
                            }
                        })
                        .catch(errors => {
                            swal.close();
                            /** Display errors */
                            import('../core/errors').then(({default: displayErrors}) => {
                                new displayErrors(errors);
                            }).catch(error => console.error(error.message));
                        });

                    event.stopImmediatePropagation();
                    return false;

                }, 1500);
            });
        });
    }

    drEvent.on('dropify.afterClear', function (event, element) {
        // alert('File deleted');
    });

    drEvent.on('dropify.errors', function (event, element) {
        console.log('Dropify errors');
    });
}