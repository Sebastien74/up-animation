import 'dropify/dist/css/dropify.css';
import "dropify";

/**
 * Dropify
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let trans = document.getElementById('data-translation');
    let dropifyElements = document.querySelectorAll('.dropify');

    if (dropifyElements.length > 0 && typeof jQuery !== 'undefined' && typeof jQuery.fn.dropify !== 'undefined') {
        let drEvent = jQuery(dropifyElements).dropify({
            messages: {
                'default': trans.dataset.dropifyDefault,
                'replace': trans.dataset.dropifyReplace,
                'remove': trans.dataset.dropifyRemove,
                'error': trans.dataset.dropifyError,
            },
            error: {
                'fileSize': trans.dataset.dropifyFileSize,
                'minWidth': trans.dataset.dropifyMinWidth,
                'maxWidth': trans.dataset.dropifyMaxWidth,
                'minHeight': trans.dataset.dropifyMinHeight,
                'maxHeight': trans.dataset.dropifyMaxHeight,
                'imageFormat': trans.dataset.dropifyImageFormat,
                'fileExtension': trans.dataset.dropifyFileExtension
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
}