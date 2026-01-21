import '../../../scss/admin/lib/sweetalert.scss';
import '../lib/sweetalert/sweetalert.min';

/**
 * On delete alert
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (e, el) {

    let body = $('body');
    let trans = $('#data-translation');
    let href = el.attr('href');
    let type = el.data('type');
    let reload = el.data('reload');
    let stripePreloader = el.closest('.refer-preloader').find('.stripe-preloader');
    let loader = stripePreloader.length > 0 ? stripePreloader : body.find('.main-preloader');
    let target = type === 'collection' ? el.closest('.prototype') : $(el.data('target'));
    let postForm = typeof el.data('post-form') !== 'undefined' ? el.data('post-form') : true;

    if (target.length === 0) {
        target = el.closest('.ui-value');
    }

    let postParentForm = function (el) {

        let parentForm = el.closest('form');

        if (parentForm.length > 0) {

            let masterFormId = parentForm.attr('id');
            let formData = new FormData(document.getElementById(masterFormId));
            parentForm.addClass('is-submit');

            $.ajax({
                url: parentForm.attr('action') + "?ajax=true",
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
                error: function (errors) {
                    /** Display errors */
                    import('../core/errors').then(({default: displayErrors}) => {
                        new displayErrors(errors);
                    }).catch(error => console.error(error.message));
                }
            });
        }
    }

    swal({
        title: trans.data('swal-delete-title'),
        text: trans.data('swal-delete-text'),
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: trans.data('swal-delete-confirm-text'),
        cancelButtonText: trans.data('swal-delete-cancel-text'),
        closeOnConfirm: false
    }, function () {

        body.find('.sa-button-container .confirm').attr('disabled', '');
        body.find('.sa-button-container .cancel').attr('disabled', '');

        if (href === '') {
            target.remove();
            setTimeout(function () {
                swal(trans.data('swal-delete-success'), trans.data('swal-delete-success-text'), "success");
                swal.close();
            }, 1500);
            return true;
        }

        let url = href + '?ajax=true';
        if (href.indexOf('?') > -1) {
            url = href + '&ajax=true'
        }

        $.ajax({
            url: url,
            type: "DELETE",
            processData: false,
            contentType: false,
            async: true,
            dataType: 'json',
            beforeSend: function () {
                el.closest('.parent-item').remove();
            },
            success: function (response) {
                swal(trans.data('swal-delete-success'), trans.data('swal-delete-success-text'), "success");
                if (response.success && target) {
                    target.remove();
                    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                        backdrop.remove();
                    });
                    const body = document.body;
                    body.removeAttribute('style');
                }
                if (response.success && postForm) {
                    postParentForm(el);
                }
                if (response.success && response.reload || reload !== '') {
                    loader.removeClass('d-none');
                    swal.close();
                    if ('stay' === el.data('type')) {
                        if (loader) {
                            loader.addClass('d-none');
                        }
                    } else {
                        $('#main-preloader').removeClass('d-none');
                        setTimeout(function () {
                            window.location.href = typeof response.redirection !== 'undefined' ? response.redirection : window.location.href;
                        }, 100);
                    }
                }
                swal.close();
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