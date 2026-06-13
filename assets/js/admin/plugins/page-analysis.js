import Modal from '../bootstrap/dist/modal';
import Tooltip from '../bootstrap/dist/tooltip';

/**
 * Analyze the current page front rendering (AJAX) and display the report in a modal.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (e, el) {

    let body = document.body;
    let href = el.getAttribute('href');
    let loader = document.getElementById('main-preloader') || body.querySelector('.main-preloader');

    if (loader instanceof HTMLElement) {
        loader.classList.remove('d-none');
    }

    let url = href + (href.indexOf('?') > -1 ? '&ajax=true' : '?ajax=true');

    fetch(url, {
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw text; });
            }
            return response.json();
        })
        .then(response => {
            if (loader instanceof HTMLElement) {
                loader.classList.add('d-none');
            }
            if (!response.html) {
                return;
            }
            document.querySelectorAll('#modal-page-analysis').forEach(modal => {
                let wrapper = modal.closest('.modal-wrapper');
                (wrapper || modal).remove();
            });

            let wrapper = document.createElement('div');
            wrapper.innerHTML = response.html.trim();
            body.appendChild(wrapper);

            let modalEl = document.getElementById('modal-page-analysis');
            if (modalEl) {
                Modal.getOrCreateInstance(modalEl).show();
                modalEl.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tip => Tooltip.getOrCreateInstance(tip));
                modalEl.addEventListener('hidden.bs.modal', function () {
                    document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                    body.removeAttribute('style');
                    wrapper.remove();
                });
            }
        })
        .catch(errors => {
            if (loader instanceof HTMLElement) {
                loader.classList.add('d-none');
            }
            import('../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
        });
}
