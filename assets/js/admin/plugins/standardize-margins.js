/**
 * On standardize margins alert
 *
 * Copies the desktop margins/paddings to the other screens (laptop, tablet, mobile)
 * for the whole zone (cols and blocks included), then reloads to reflect the result.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (e, el) {

    import('../lib/sweetalert/sweetalert.min').then(() => {

        import('../../../scss/admin/lib/sweetalert.scss');

        let body = document.body;
        let trans = document.getElementById('data-translation');
        let href = el.getAttribute('href');
        let loader = document.getElementById('main-preloader') || body.querySelector('.main-preloader');

        swal({
            title: trans.dataset.swalTitle,
            text: trans.dataset.swalText,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: trans.dataset.swalConfirmText,
            cancelButtonText: trans.dataset.swalCancelText,
            closeOnConfirm: false
        }, function () {

            let confirmBtn = body.querySelector('.sa-button-container .confirm');
            let cancelBtn = body.querySelector('.sa-button-container .cancel');
            if (confirmBtn) confirmBtn.setAttribute('disabled', '');
            if (cancelBtn) cancelBtn.setAttribute('disabled', '');

            let url = href + (href.indexOf('?') > -1 ? '&ajax=true' : '?ajax=true');

            fetch(url, {
                method: "DELETE",
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': trans.getAttribute('data-csrf-delete') || ''
                }
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => { throw text; });
                    }
                    return response.json();
                })
                .then(response => {
                    swal(trans.dataset.swalSuccess, trans.dataset.swalText, "success");
                    if (response.success) {
                        if (loader instanceof HTMLElement) {
                            loader.classList.remove('d-none');
                        }
                        setTimeout(function () {
                            window.location.reload();
                        }, 100);
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

    }).catch(error => console.error("Could not load SweetAlert:", error));
}
