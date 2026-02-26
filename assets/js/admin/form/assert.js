/**
 * Assert
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    /** Modals form errors */
    let modalError = false;

    const modals = document.querySelectorAll('.modal');
    modals.forEach(function (modalEl) {
        const invalids = modalEl.querySelectorAll('.invalid-feedback');

        /** If is an invalid form modal */
        if (invalids.length > 0 && !modalError) {
            const modal = typeof bootstrap !== 'undefined' ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
            if (modal) modal.show();
            modalError = true;

            /** On close modal : Reset form */
            modalEl.addEventListener('hidden.bs.modal', function () {
                modalEl.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
                modalEl.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            }, {once: true});
        }
    });
}