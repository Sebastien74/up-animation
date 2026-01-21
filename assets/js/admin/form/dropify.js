import 'dropify/dist/css/dropify.css';
import '../../../scss/admin/lib/sweetalert.scss';

import "dropify";
import '../lib/sweetalert/sweetalert.min';

import route from "../../vendor/components/routing";

/**
 * Dropify
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let trans = $('#data-translation');

    let drEvent = $('.dropify').dropify({
        messages: {
            'default': trans.data('dropify-default'),
            'replace': trans.data('dropify-replace'),
            'remove': trans.data('dropify-remove'),
            'error': trans.data('dropify-error'),
        },
        error: {
            'fileSize': trans.data('dropify-file-size'),
            'minWidth': trans.data('dropify-min-width'),
            'maxWidth': trans.data('dropify-max-width'),
            'minHeight': trans.data('dropify-min-height'),
            'maxHeight': trans.data('dropify-max-height'),
            'imageFormat': trans.data('dropify-image-format'),
            'fileExtension': trans.data('dropify-file-extension')
        }
    });

    let indexPage = $('#entities-index');

    if (indexPage.length === 0) {

        drEvent.on('dropify.beforeClear', function (event, element) {

            event.result = false;

            return swal({
                title: trans.data('swal-delete-title'),
                text: trans.data('swal-delete-text'),
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: trans.data('swal-delete-confirm-text'),
                cancelButtonText: trans.data('swal-delete-cancel-text'),
                closeOnConfirm: false
            }, function () {

                let body = $('body');

                body.find('.sa-button-container .confirm').attr('disabled', '');
                body.find('.sa-button-container .cancel').attr('disabled', '');

                setTimeout(function () {

                    let input = element.input;
                    let customUrl = input.data('delete-url');
                    let screen = input.data('screen');
                    let media = input.data('media');
                    let url = route('admin_mediarelation_reset_media', {
                        "website": body.data('id'),
                        "mediaRelationId": input.data('media-relation'),
                        "mediaClassname": input.data('media-classname'),
                    });

                    if (typeof customUrl != 'undefined') {
                        url = customUrl;
                    } else if (typeof media != 'undefined') {
                        url = route('admin_media_delete', {"website": body.data('id'), "media": media});
                    }

                    let ajaxUrl = url + "?ajax=true";
                    if (url.indexOf('?') > -1) {
                        ajaxUrl = url + "&ajax=true";
                    }

                    $.ajax({
                        url: ajaxUrl,
                        type: "DELETE",
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                element.resetFile();
                                input.val('');
                                element.resetPreview();
                                input.trigger($.Event("dropify.afterClear"), [this]);
                            }
                            swal.close();
                            if (typeof media != 'undefined' && typeof screen == 'undefined') {
                                location.reload();
                            }
                        },
                        error: function (errors) {
                            swal.close();
                            /** Display errors */
                            import('../core/errors').then(({default: displayErrors}) => {
                                new displayErrors(errors);
                            }).catch(error => console.error(error.message));
                        }
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