import '../../../scss/admin/lib/sweetalert.scss';
import '../lib/sweetalert/sweetalert.min';

/**
 * Confirm link alert
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (e, el) {

    let body = $('body');
    let trans = $('#data-translation');
    let href = el.attr('href');
    let reload = el.data('reload');

    swal({
        title: trans.data('swal-title'),
        text: trans.data('swal-text'),
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: trans.data('swal-confirm-text'),
        cancelButtonText: trans.data('swal-cancel-text'),
        closeOnConfirm: false
    }, function () {

        body.find('.sa-button-container .confirm').attr('disabled', '');
        body.find('.sa-button-container .cancel').attr('disabled', '');

        let url = href + '?ajax=true';
        if (href.indexOf('?') > -1) {
            url = href + '&ajax=true'
        }

        $.ajax({
            url: url,
            type: "GET",
            processData: false,
            contentType: false,
            async: true,
            dataType: 'json',
            beforeSend: function () {
            },
            success: function (response) {

                swal(trans.data('swal-success'), trans.data('swal-success-text'), "success");

                if (response.success && response.reload || reload !== '') {
                    swal.close();
                    location.reload();
                }
            },
            error: function (errors) {
                /** Display errors */
                import('../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            }
        });

        e.stopImmediatePropagation();
        return false;
    });
}