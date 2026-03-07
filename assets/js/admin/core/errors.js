import '../bootstrap/dist/modal';
import '../bootstrap/dist/alert';
import {AlertHTML} from '../functions';

/**
 * To display Errors messages
 */
export default function (error = null, element = null) {

    if (!error) {
        return false;
    }

    let isDebug = document.documentElement.dataset.debug;

    if (error.status !== 200 && isDebug) {

        const body = document.body;

        document.querySelectorAll('.alert').forEach(el => el.remove());
        const mainPreloader = document.querySelector(".main-preloader");
        if (mainPreloader) {
            mainPreloader.style.transition = 'opacity 0.5s ease-out';
            mainPreloader.style.opacity = '0';
            setTimeout(() => mainPreloader.style.display = 'none', 500);
        }

        let trans = document.getElementById('data-translation');
        let text = error;
        let status = 500;
        let statusText = trans ? trans.dataset.internalError : 'Internal Error';

        if (typeof error === 'string') {
            text = error;
        } else if (error) {
            // JSON.parse / SyntaxError / Error
            if (typeof error.message === 'string' && error.message.trim() !== '') {
                text = error.message;
            }
            // XHR / fetch-like
            else if (typeof error.responseText === 'string' && error.responseText.trim() !== '') {
                text = error.responseText;
            }
            // Ajax: errorThrown
            else if (typeof error.statusText === 'string' && error.statusText.trim() !== '') {
                text = error.statusText;
            } else if (typeof error.toString === 'function') {
                text = String(error);
            }
            if (typeof error.status === 'number') status = error.status;
            if (typeof error.statusText === 'string') statusText = error.statusText;
        }

        const adminBody = document.getElementById('admin-body');
        let blockToDisplay = element === null ? adminBody : (element.length > 0 || element instanceof HTMLElement ? element : adminBody);

        if (body.classList.contains('internal') && typeof text != 'undefined') {
            if (blockToDisplay) blockToDisplay.insertAdjacentHTML('afterbegin', AlertHTML(text));
        }

        if (status !== 0 && statusText !== "error") {
            const text = '<strong class="me-2">' + (trans ? trans.dataset.error : 'Error') + ' ' + status + '</strong>' + statusText;
            if (blockToDisplay) blockToDisplay.insertAdjacentHTML('afterbegin', AlertHTML(text));
        }

        document.querySelectorAll('.stripe-preloader').forEach(el => el.classList.add('d-none'));
        if (typeof bootstrap !== 'undefined') {
            document.querySelectorAll('.modal').forEach(el => {
                const modal = bootstrap.Modal.getInstance(el);
                if (modal) modal.hide();
            });
            document.querySelectorAll('.alert').forEach(el => {
                new bootstrap.Alert(el);
            });
        }

        const firstError = document.querySelector('.internal-error-alert');

        if (firstError) {
            const rect = firstError.getBoundingClientRect();
            const elOffset = rect.top + window.pageYOffset;
            const elHeight = rect.height;
            const windowHeight = window.innerHeight;
            let offset;
            if (elHeight < windowHeight) {
                offset = elOffset - ((windowHeight / 2) - (elHeight / 2));
            } else {
                offset = elOffset;
            }
            window.scrollTo({
                top: offset,
                behavior: 'smooth'
            });
        }
    }
}