import removeErrors from "../../vendor/components/remove-errors";

import '../../../scss/admin/pages/menu.scss';

$('body').on('click', '#link_save', function (e) {

    e.preventDefault();

    let loader = $('body').find('.main-preloader');
    let el = $(this);
    let form = el.closest('form');
    let formId = form.attr('id');
    let formData = new FormData(document.getElementById(formId));

    $.ajax({
        url: form.attr('action') + '?ajax=true',
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        async: true,
        beforeSend: function () {
            removeErrors();
            loader.removeClass('d-none');
        },
        success: function (response) {

            if (response.html && !response.success) {

                let html = $(response.html).find("#link-form-content")[0];
                let ajaxContent = form.find("#link-form-content");

                if (ajaxContent.length === 0) {
                    ajaxContent = form.closest("#link-form-content");
                }

                ajaxContent.replaceWith(html);
                loader.addClass('d-none');
            }

            if (response.success) {
                location.href = location.href;
            }
        },
        error: function (errors) {
            /** Display errors */
            import('../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
            loader.addClass('d-none');
        }
    });

    e.stopImmediatePropagation();
    return false;
});