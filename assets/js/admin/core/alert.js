import {AlertHTML} from '../functions';

/**
 * To display alert messages
 */
export default function (message, type = 'info', element = null, removeOld = true) {
    if (removeOld === true) {
        document.querySelectorAll('.alert').forEach(el => el.remove());
    }
    let blockToDisplay = element === null ? document.getElementById('admin-body') : element;
    if (typeof element === 'string') {
        blockToDisplay = document.querySelector(element);
    }
    if (blockToDisplay) {
        blockToDisplay.insertAdjacentHTML('afterbegin', AlertHTML(message));
    }
}