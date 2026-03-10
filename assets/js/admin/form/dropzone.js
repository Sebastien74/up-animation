import Dropzone from "dropzone";
import masterDropzoneForm from "../media/master-dropzone-form";
import {AlertHTML} from '../functions';

/**
 * Dropzone
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let trans = document.getElementById('data-translation');
    let referenceClass = '.js-reference-dropzone';
    let form = document.querySelector('body ' + referenceClass);

    if (!form) {
        return;
    }

    import('dropzone/dist/dropzone.css');
    import('../../../scss/admin/lib/dropzone.scss');

    Dropzone.autoDiscover = false;

    let field = form.querySelector('.dropzone-field');

    let url = form.getAttribute('action');
    if (url.indexOf('?') > -1) {
        url = url + "&ajax=1";
    } else {
        url = url + "?ajax=1";
    }

    let dropzone = new Dropzone(referenceClass, {
        url: url,
        paramName: field.getAttribute('name'),
        maxFilesize: 100,
        timeout: 300000,
        acceptedFiles: field.getAttribute('accept'),
        dictDefaultMessage: '<i class="icm-download mb-2 d-inline-block"></i><br>' + trans.getAttribute('data-dropzone-default-message'),
        dictFallbackMessage: trans.getAttribute('data-dropzone-fallback-message'),
        dictFallbackText: trans.getAttribute('data-dropzone-invalid-file-type'),
        dictFileTooBig: trans.getAttribute('data-dropzone-file-too-big'),
        dictInvalidFileType: trans.getAttribute('data-dropzone-invalid-file-type'),
        dictResponseError: trans.getAttribute('data-dropzone-response-error'),
        dictCancelUpload: trans.getAttribute('data-dropzone-cancel-upload'),
        dictCancelUploadConfirmation: trans.getAttribute('data-dropzone-cancel-upload-confirmation'),
        dictRemoveFile: trans.getAttribute('data-dropzone-remove-file'),
        dictMaxFilesExceeded: trans.getAttribute('data-dropzone-max-files-exceeded')
    });

    dropzone.on("sending", function (file, response) {
        masterDropzoneForm();
    });

    dropzone.on("success", function (file, response) {
        if (response.errors) {
            displayErrors(response);
            document.body.setAttribute('data-dropzone-success', 'false');
        }
    });

    dropzone.on("error", function (file, response) {
        displayErrors(response);
    });

    dropzone.on("queuecomplete", function (file, response) {
        let body = document.body;
        let success = body.getAttribute('data-dropzone-success');
        if (success === null) {
            let preloader = body.querySelector('.main-preloader');
            if (preloader) {
                preloader.classList.remove('d-none');
            }
            window.location.href = window.location.href;
        }
        body.removeAttribute('data-dropzone-success');
    });

    function displayErrors(errors) {
        const error = typeof errors === 'string' ? errors : (typeof errors.errors === 'string' ? errors.errors : 'Une erreur est survenue !');
        let errorsWrap = document.getElementById('dropzone-errors');
        errorsWrap = errorsWrap ? errorsWrap : document.getElementById('admin-body');
        errorsWrap.insertAdjacentHTML("afterbegin", AlertHTML(error));
        errorsWrap.scrollIntoView({behavior: "smooth", block: "end", inline: "nearest"});
    }
}