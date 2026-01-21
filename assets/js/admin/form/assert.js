/**
 * Assert
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    $(function () {

        /** Modals form errors */
        let modalError = false;

        $('body').find('.modal').each(function () {

            let asInvalid = $(this).find('.invalid-feedback');

            /** If is an invalid form modal */
            if (asInvalid.length > 0 && !modalError) {

                let modalAsError = asInvalid.closest('.modal');
                modalAsError.modal('show');
                modalError = true;

                /** On close modal : Reset form */
                modalAsError.on('hidden.bs.modal', function (e) {
                    modalAsError.find('.invalid-feedback').remove();
                    modalAsError.find('.is-invalid').removeClass('is-invalid');
                });
            }
        });
    });
}