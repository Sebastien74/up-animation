export default function () {

    let body = document.body;

    if (body.querySelector('.index-container')) {
        import('../lib/sweetalert/sweetalert.min.js').then(() => {
            import('../../../scss/admin/lib/sweetalert.scss');
        });
    }

    let removeAllBtns = body.querySelectorAll('.delete-index-all');

    body.querySelectorAll('.delete-input-index').forEach(input => input.checked = false);
    removeAllBtns.forEach(btn => btn.checked = false);

    body.addEventListener('click', function (e) {

        let target = e.target.closest('.index-delete-show');

        console.log(target);
        if (!target) return;

        e.preventDefault();

        let el = target;
        let isActive = el.classList.contains('active');
        let container = el.closest('.index-container');
        let inputs = container.querySelectorAll('.delete-input-index');
        let removeAllBtn = container.querySelector('.delete-index-all');

        if (isActive) {
            inputs.forEach(input => {
                let parent = input.parentElement;
                parent.classList.remove('d-inline-block');
                parent.classList.add('d-none');
            });
            if (removeAllBtn) {
                removeAllBtn.parentElement.classList.add('d-none');
            }
            el.setAttribute('data-original-title', el.getAttribute('data-display'));
            if (typeof bootstrap !== 'undefined') {
                let tooltip = bootstrap.Tooltip.getOrCreateInstance(el);
                tooltip.show();
            }
            el.classList.remove('active');
        } else {
            inputs.forEach(input => {
                let parent = input.parentElement;
                parent.classList.remove('d-none');
                parent.classList.add('d-inline-block');
            });
            if (removeAllBtn) {
                removeAllBtn.parentElement.classList.remove('d-none');
            }
            el.setAttribute('data-original-title', el.getAttribute('data-hide'));
            if (typeof bootstrap !== 'undefined') {
                let tooltip = bootstrap.Tooltip.getOrCreateInstance(el);
                tooltip.show();
            }
            el.classList.add('active');
        }
    });

    let inputChecked = function (card) {

        let inputsChecked = card.querySelectorAll('.delete-input-index:checked');
        let removeBtn = card.querySelector('.index-delete-submit');
        let showBtn = card.querySelector('.index-delete-show');

        if (inputsChecked.length > 0) {
            if (removeBtn) removeBtn.classList.remove('d-none');
            if (showBtn) showBtn.classList.add('d-none');
        } else {
            if (removeBtn) removeBtn.classList.add('d-none');
            if (showBtn) showBtn.classList.remove('d-none');
        }
    };

    body.addEventListener('change', function (e) {

        let el = e.target.closest('.delete-index-all');
        if (!el) return;

        let container = el.closest('.index-container');
        let isChecked = el.checked;
        let allInputs = container.querySelectorAll('.delete-input-index');
        let parent = el.parentElement;

        if (isChecked) {
            parent.setAttribute('data-original-title', parent.getAttribute('data-unchecked'));
            allInputs.forEach(input => input.checked = true);
        } else {
            parent.setAttribute('data-original-title', parent.getAttribute('data-checked'));
            allInputs.forEach(input => input.checked = false);
        }

        if (typeof bootstrap !== 'undefined') {
            let tooltip = bootstrap.Tooltip.getOrCreateInstance(parent);
            tooltip.show();
        }

        inputChecked(container);
    });

    body.addEventListener('change', function (e) {
        let el = e.target.closest('.delete-input-index');
        if (!el) return;
        let container = el.closest('.index-container');
        inputChecked(container);
    });

    body.addEventListener('click', function (e) {

        let target = e.target.closest('.index-delete-submit');
        if (!target) return;

        e.preventDefault();

        let trans = document.getElementById('data-translation');
        let container = target.closest('.index-container');

        import('../lib/sweetalert/sweetalert.min.js').then(() => {
            swal({
                title: trans.getAttribute('data-swal-delete-title'),
                text: trans.getAttribute('data-swal-delete-text'),
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: trans.getAttribute('data-swal-delete-confirm-text'),
                cancelButtonText: trans.getAttribute('data-swal-delete-cancel-text'),
                closeOnConfirm: false
            }, function () {

                body.querySelectorAll('.sa-button-container .confirm, .sa-button-container .cancel').forEach(btn => btn.disabled = true);

                container.querySelectorAll('.delete-input-index').forEach(function (el) {

                    if (el.checked) {

                        let path = el.getAttribute('data-path');
                        let url = path + (path.indexOf('?') > -1 ? '&ajax=true' : '?ajax=true');

                        fetch(url, {
                            method: "DELETE",
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(response => response.json())
                            .then(response => {
                                let tr = el.closest('tr');
                                if (tr) {
                                    tr.style.transition = 'opacity 0.2s';
                                    tr.style.opacity = '0';
                                    setTimeout(() => tr.remove(), 200);
                                }
                            })
                            .catch(errors => {
                                /** Display errors */
                                import('../core/errors').then(({default: displayErrors}) => {
                                    new displayErrors(errors);
                                }).catch(error => console.error(error.message));
                            });
                    }
                });

                setTimeout(function () {
                    swal.close();
                }, 1500);

                let inputs = container.querySelectorAll('.delete-input-index');
                let removeBtn = container.querySelector('.index-delete-submit');
                let showBtn = container.querySelector('.index-delete-show');

                if (removeBtn) removeBtn.classList.add('d-none');
                if (showBtn) {
                    showBtn.classList.remove('d-none');
                    showBtn.setAttribute('data-original-title', showBtn.getAttribute('data-display'));
                    if (typeof bootstrap !== 'undefined') {
                        let tooltip = bootstrap.Tooltip.getOrCreateInstance(showBtn);
                        tooltip.show();
                    }
                }
                inputs.forEach(input => {
                    input.parentElement.classList.add('d-none');
                    input.parentElement.classList.remove('d-inline-block');
                });
            });
        }).catch(error => console.error("Could not load SweetAlert:", error));

        e.stopImmediatePropagation();
        return false;
    });
}