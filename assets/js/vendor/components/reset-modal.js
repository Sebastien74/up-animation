/**
 * Reset modal
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (modal, remove = false) {
    let backdrops = document.querySelectorAll('body .modal-backdrop');
    if (backdrops.length > 0) {
        backdrops[backdrops.length - 1].remove();
    }
    document.body.classList.remove('modal-open');
    document.body.removeAttribute('style');
    if (remove) {
        modal.remove();
    } else if (typeof bootstrap !== 'undefined') {
        let bsModal = bootstrap.Modal.getOrCreateInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    } else if (typeof jQuery !== 'undefined' && typeof jQuery.fn.modal !== 'undefined') {
        jQuery(modal).modal('hide');
    }
}