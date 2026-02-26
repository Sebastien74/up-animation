import layoutActivation from './vendor';
import Tooltip from '../../bootstrap/dist/tooltip';

/**
 * Refresh layout
 */
export default function (Routing, form, modal, event) {

    let body = document.body;
    let formEl = form instanceof HTMLElement ? form : form[0];
    if (!formEl) return;

    let formData = new FormData(formEl);
    let action = formEl.getAttribute('action') || '';
    let loader = body.querySelector('#layout-preloader');
    let scrollElementSelector = formEl.getAttribute('data-scroll-to');

    if (modal) {
        let modalEl = modal instanceof HTMLElement ? modal : modal[0];
        if (modalEl) {
            modalEl.remove();
        }
        body.classList.remove('modal-open');
        body.removeAttribute('style');
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    }

    let urlWithAjax = action.indexOf('?') > -1 ? action + '&ajax=true' : action + '?ajax=true';

    if (loader) {
        loader.classList.toggle('d-none');
    }

    fetch(urlWithAjax, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(() => {
            let currentUrl = window.location.href;
            let refreshUrl = currentUrl.indexOf('?') > -1 ? currentUrl + '&ajax=true' : currentUrl + '?ajax=true';

            return fetch(refreshUrl);
        })
        .then(response => response.json())
        .then(response => {
            let tempDiv = document.createElement('div');
            tempDiv.innerHTML = response.html;
            let newLayoutGrid = tempDiv.querySelector("#layout-grid");
            let currentLayoutGrid = document.querySelector("#layout-grid");

            if (newLayoutGrid && currentLayoutGrid) {
                currentLayoutGrid.replaceWith(newLayoutGrid);
            }

            if (scrollElementSelector) {
                let scrollElement = document.querySelector(scrollElementSelector);
                if (scrollElement) {
                    window.scrollTo({
                        top: scrollElement.getBoundingClientRect().top + window.scrollY,
                        behavior: 'smooth'
                    });
                }
            }

            layoutActivation(Routing);

            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                new Tooltip(el, {trigger: "hover"});
            });

            if (loader) {
                loader.classList.toggle('d-none');
            }

            let popupImages = document.querySelectorAll('.glightbox');
            if (popupImages.length > 0) {
                import('../../../vendor/plugins/popup').then(({default: popup}) => {
                    new popup();
                }).catch(error => console.error(error.message));
            }
        })
        .catch(errors => {
            /** Display errors */
            import('../../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
        });

    if (event) {
        event.stopPropagation();
    }
    return false;
}