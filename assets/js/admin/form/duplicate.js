import resetModal from "../../vendor/components/reset-modal";
import select2 from '../../vendor/plugins/select2'

/**
 * Duplicate form
 */
export default function () {

    /** Show duplicate modal */
    document.body.addEventListener('click', function (e) {
        const target = e.target.closest('.duplicate-btn');
        if (!target) return;

        const body = document.body;
        let loader = body.querySelector('#main-preloader');
        const loaderData = target.getAttribute('data-preloader');
        if (typeof loaderData !== 'undefined' && loaderData) {
            loader = document.querySelector(loaderData) || loader;
        }

        const path = target.getAttribute('data-path');
        let url = path + (path.indexOf('?') > -1 ? '&ajax=true' : '?ajax=true');

        if (loader) loader.classList.toggle('d-none');

        fetch(url, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json())
            .then(response => {

                if (response.redirection) {
                    window.location.href = response.redirection;
                    return;
                }

                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = response.html;
                const modal = tempDiv.querySelector('.modal');
                document.body.insertAdjacentHTML('beforeend', response.html);

                const modalEl = document.getElementById(modal ? modal.getAttribute('id') : '');

                if (modalEl && typeof bootstrap !== 'undefined') {
                    const bsModal = new bootstrap.Modal(modalEl);
                    bsModal.show();
                }
                if (loader) loader.classList.toggle('d-none');

                select2();
                import('./ajax').then(({default: ajaxForm}) => {
                    new ajaxForm();
                }).catch(error => console.error(error.message));

                if (modalEl) {
                    modalEl.addEventListener('hide.bs.modal', function () {
                        resetModal(modalEl, true);
                        const wrapper = document.querySelector('.modal-wrapper');
                        if (wrapper) wrapper.remove();
                    });
                }
            })
            .catch(errors => {
                /** Display errors */
                import('../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));

                const modal = document.querySelector('.modal');
                if (modal) {
                    resetModal(modal, true);
                }
            });

        e.stopImmediatePropagation();
        return false;
    });
}