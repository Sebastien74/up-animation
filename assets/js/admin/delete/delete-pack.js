import displayAlert from "../core/alert";

/**
 * Delete pack
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    if (document.querySelector('.delete-pack')) {
        import('../lib/sweetalert/sweetalert.min.js').then(() => {
            import('../../../scss/admin/lib/sweetalert.scss');
        });
    }

    function setRows(element) {
        let parentRow = element.closest('.parent-row');
        if (parentRow && parentRow.dataset.level !== undefined) {
            if (element.checked) {
                parentRow.querySelectorAll('ol .parent-row').forEach(row => {
                    let checkbox = row.querySelector('.delete-pack');
                    if (checkbox) checkbox.checked = true;
                });
            } else {
                let current = parentRow;
                while (current) {
                    let parent = current.parentElement.closest('.parent-row');
                    if (parent) {
                        let content = parent.querySelector('.dd3-content');
                        if (content) {
                            let checkbox = content.querySelector('.delete-pack');
                            if (checkbox) checkbox.checked = false;
                        }
                        current = parent;
                    } else {
                        current = null;
                    }
                }
            }
        }
    }

    /** Check elements to delete for hide or display deletion btn */
    function showBtn() {

        let hideBtn = true;
        document.querySelectorAll('.delete-pack').forEach(el => {
            if (el.checked) {
                hideBtn = false;
            }
        });

        let deleteBtn = document.getElementById('delete-pack-btn');
        if (deleteBtn) {
            if (hideBtn) {
                deleteBtn.classList.add('d-none');
            } else {
                deleteBtn.classList.remove('d-none');
            }
        }
    }

    function removeItems() {

        let body = document.body;

        body.addEventListener('click', function (e) {

            let target = e.target.closest('#delete-pack-btn');
            if (!target) return;

            e.preventDefault();

            target.classList.add('d-none');

            let trans = document.getElementById('data-translation');
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

                    document.querySelectorAll('.delete-pack').forEach(function (el) {

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
                                    if (response.alert && response.alert === 'error') {
                                        displayAlert(response.message, 'danger', null, false);
                                        window.scrollTo({top: 0, behavior: 'slow'});
                                    } else {
                                        let li = el.closest('li.parent-row');
                                        let otherParent = el.closest('.delete-pack-parent-row');
                                        if (li) {
                                            li.style.transition = 'opacity 0.2s';
                                            li.style.opacity = '0';
                                            setTimeout(() => li.remove(), 200);
                                        }
                                        if (otherParent) {
                                            otherParent.style.transition = 'opacity 0.2s';
                                            otherParent.style.opacity = '0';
                                            setTimeout(() => otherParent.remove(), 200);
                                        }
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
                });
            }).catch(error => console.error("Could not load SweetAlert:", error));

            e.stopImmediatePropagation();
            return false;
        });
    }

    document.querySelectorAll('.delete-pack').forEach(el => el.checked = false);

    document.body.addEventListener('change', function (e) {
        let el = e.target.closest('.delete-pack');
        if (el) {
            setRows(el);
            showBtn();
            removeItems();
        }
    });
}