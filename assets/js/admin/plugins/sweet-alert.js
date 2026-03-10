/**
 * On delete alert
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (e, el) {

    import('../../../scss/admin/lib/sweetalert.scss');

    let body = document.body;
    let trans = document.getElementById('data-translation');
    let href = el.getAttribute('href');
    let type = el.dataset.type;
    let reload = el.dataset.reload || el.dataset.count;
    let stripePreloader = el.closest('.refer-preloader')?.querySelector('.stripe-preloader');
    let loader = stripePreloader || body.querySelector('.main-preloader');
    let target = type === 'collection' ? el.closest('.prototype') : document.querySelector(el.dataset.target);
    let postForm = el.dataset.postForm !== 'undefined' ? el.dataset.postForm === 'true' : true;

    if (!target) {
        target = el.closest('.ui-value');
    }

    let postParentForm = function (el) {

        let parentForm = el.closest('form');

        if (parentForm) {

            let masterFormId = parentForm.getAttribute('id');
            let formData = new FormData(document.getElementById(masterFormId));
            parentForm.classList.add('is-submit');

            let action = parentForm.getAttribute('action');

            fetch(action + "?ajax=true", {
                method: "POST",
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => { throw text; });
                    }
                    return response.json();
                })
                .then(response => {
                    // Success
                })
                .catch(errors => {
                    /** Display errors */
                    import('../core/errors').then(({default: displayErrors}) => {
                        new displayErrors(errors);
                    }).catch(error => console.error(error.message));
                });
        }
    }

    swal({
        title: trans.dataset.swalDeleteTitle,
        text: trans.dataset.swalDeleteText,
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: trans.dataset.swalDeleteConfirmText,
        cancelButtonText: trans.dataset.swalDeleteCancelText,
        closeOnConfirm: false
    }, function () {

        let confirmBtn = body.querySelector('.sa-button-container .confirm');
        let cancelBtn = body.querySelector('.sa-button-container .cancel');
        if (confirmBtn) confirmBtn.setAttribute('disabled', '');
        if (cancelBtn) cancelBtn.setAttribute('disabled', '');

        if (href === '' || href === '#') {
            if (target) {
                target.remove();
            }
            setTimeout(function () {
                swal(trans.dataset.swalDeleteSuccess, trans.dataset.swalDeleteSuccessText, "success");
                swal.close();
            }, 1500);
            return true;
        }

        let url = href + '?ajax=true';
        if (href.indexOf('?') > -1) {
            url = href + '&ajax=true'
        }

        fetch(url, {
            method: "DELETE"
        })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw text; });
                }
                return response.json();
            })
            .then(response => {
                swal(trans.dataset.swalDeleteSuccess, trans.dataset.swalDeleteSuccessText, "success");
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
                if (response.success && response.reload || (reload !== undefined && reload !== null && reload !== '')) {
                    if (loader instanceof HTMLElement) {
                        loader.classList.remove('d-none');
                    }
                    swal.close();
                    let elType = el.dataset.type;
                    if ('stay' === elType) {
                        if (loader) {
                            loader.classList.add('d-none');
                        }
                    } else {
                        let mainPreloader = document.getElementById('main-preloader');
                        if (mainPreloader) {
                            mainPreloader.classList.remove('d-none');
                        }
                        setTimeout(function () {
                            window.location.href = typeof response.redirection !== 'undefined' ? response.redirection : window.location.href;
                        }, 100);
                    }
                }
                swal.close();
            })
            .catch(errors => {
                /** Display errors */
                import('../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            });

        e.stopImmediatePropagation();
        return false;
    });
}