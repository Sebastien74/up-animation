export default function (e, el) {

    import('../../../scss/admin/lib/sweetalert.scss');

    let trans = document.getElementById('data-translation');
    let href = el instanceof jQuery ? el.attr('href') : el.getAttribute('href');
    let reload = el instanceof jQuery ? el.data('reload') : el.getAttribute('data-reload');

    swal({
        title: trans.getAttribute('data-swal-title'),
        text: trans.getAttribute('data-swal-text'),
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: trans.getAttribute('data-swal-confirm-text'),
        cancelButtonText: trans.getAttribute('data-swal-cancel-text'),
        closeOnConfirm: false
    }, function () {

        let confirmBtn = document.querySelector('.sa-button-container .confirm');
        let cancelBtn = document.querySelector('.sa-button-container .cancel');
        if (confirmBtn) confirmBtn.setAttribute('disabled', '');
        if (cancelBtn) cancelBtn.setAttribute('disabled', '');

        let url = href + (href.indexOf('?') > -1 ? '&ajax=true' : '?ajax=true');

        fetch(url, {
            method: "GET",
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(response => {

                swal(trans.getAttribute('data-swal-success'), trans.getAttribute('data-swal-success-text'), "success");

                if (response.success && response.reload || (reload !== undefined && reload !== null && reload !== '')) {
                    swal.close();
                    location.reload();
                }
            })
            .catch(errors => {
                /** Display errors */
                import('../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            });

        if (e.stopImmediatePropagation) e.stopImmediatePropagation();
        return false;
    });
}