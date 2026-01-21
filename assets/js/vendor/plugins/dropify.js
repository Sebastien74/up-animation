import 'dropify/dist/css/dropify.css';
import "dropify";

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

    drEvent.on('dropify.beforeClear', function (event, element) {
        // alert('File beforeClear');
    });

    drEvent.on('dropify.afterClear', function (event, element) {
        // alert('File afterClear');
    });

    drEvent.on('dropify.errors', function (event, element) {
        console.log('Dropify errors');
    });
}