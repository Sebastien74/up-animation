import 'dropzone/dist/dropzone.css';
import '../../../scss/admin/lib/dropzone.scss';

import Dropzone from "dropzone";
import masterDropzoneForm from "../media/master-dropzone-form";

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

        let message = '<div class="internal-error-alert alert alert-danger position-relative d-flex p-0 mt-3">';
        message += '<div class="icon d-flex align-items-center justify-content-center position-relative">';
        message += '<svg xmlns="http://www.w3.org/2000/svg" height="22" viewBox="0 0 576 512"><path d="M248.747 204.705l6.588 112c.373 6.343 5.626 11.295 11.979 11.295h41.37a12 12 0 0 0 11.979-11.295l6.588-112c.405-6.893-5.075-12.705-11.979-12.705h-54.547c-6.903 0-12.383 5.812-11.978 12.705zM330 384c0 23.196-18.804 42-42 42s-42-18.804-42-42 18.804-42 42-42 42 18.804 42 42zm-.423-360.015c-18.433-31.951-64.687-32.009-83.154 0L6.477 440.013C-11.945 471.946 11.118 512 48.054 512H527.94c36.865 0 60.035-39.993 41.577-71.987L329.577 23.985zM53.191 455.002L282.803 57.008c2.309-4.002 8.085-4.002 10.394 0l229.612 397.993c2.308 4-.579 8.998-5.197 8.998H58.388c-4.617.001-7.504-4.997-5.197-8.997z"/></svg>';
        message += '</div>';
        message += '<div class="message px-4 py-3 w-100">';
        message += error;
        message += '</div>';
        message += '</div>';

        let errorsWrap = document.getElementById('dropzone-errors');
        errorsWrap = errorsWrap ? errorsWrap : document.getElementById('admin-body');
        errorsWrap.insertAdjacentHTML("afterbegin", message);
        errorsWrap.scrollIntoView({behavior: "smooth", block: "end", inline: "nearest"});
    }
}