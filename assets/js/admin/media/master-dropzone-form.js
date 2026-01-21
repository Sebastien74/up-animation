/**
 * Send master form on Dropzone process
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let masterForm = $('body').find('.master-dropzone-form');

    if (masterForm.length > 0 && !masterForm.hasClass('is-submit')) {

        let masterFormId = masterForm.attr('id');
        let formData = new FormData(document.getElementById(masterFormId));
        masterForm.addClass('is-submit');

        $.ajax({
            url: masterForm.attr('action') + "?ajax=true",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            async: true,
            beforeSend: function () {
            },
            success: function (response) {
            },
            error: function (error) {
                displayErrors(error);
            }
        });
    }

    function displayErrors(errors) {
        let message = '<div class="internal-error-alert alert alert-danger position-relative d-flex p-0 mt-3">';
        message += '<div class="icon d-flex align-items-center justify-content-center position-relative">';
        message += '<svg xmlns="http://www.w3.org/2000/svg" height="22" viewBox="0 0 576 512"><path d="M248.747 204.705l6.588 112c.373 6.343 5.626 11.295 11.979 11.295h41.37a12 12 0 0 0 11.979-11.295l6.588-112c.405-6.893-5.075-12.705-11.979-12.705h-54.547c-6.903 0-12.383 5.812-11.978 12.705zM330 384c0 23.196-18.804 42-42 42s-42-18.804-42-42 18.804-42 42-42 42 18.804 42 42zm-.423-360.015c-18.433-31.951-64.687-32.009-83.154 0L6.477 440.013C-11.945 471.946 11.118 512 48.054 512H527.94c36.865 0 60.035-39.993 41.577-71.987L329.577 23.985zM53.191 455.002L282.803 57.008c2.309-4.002 8.085-4.002 10.394 0l229.612 397.993c2.308 4-.579 8.998-5.197 8.998H58.388c-4.617.001-7.504-4.997-5.197-8.997z"/></svg>';
        message += '</div>';
        message += '<div class="message px-4 py-3 w-100">';
        message += errors;
        message += '</div>';
        message += '</div>';
        let dropzoneErrorsEl = $('#dropzone-errors');
        let errorsEl = dropzoneErrorsEl.length > 0 ? dropzoneErrorsEl : $('#admin-body');
        errorsEl.prepend(message);
    }
}