/**
 * Remove errors form on field click.
 */
export default function () {

    const errors = function () {
        const selectors = [
            {selector: '.form-control.is-invalid', event: 'click'},
            {selector: '.select2-selection', event: 'click'},
            {selector: '.custom-control-input', event: 'change'},
            {selector: '.form-check-input', event: 'change'},
            {selector: '.flatpicker', event: 'change'}
        ];
        selectors.forEach(({selector, event}) => {
            document.querySelectorAll(selector).forEach(field => {
                field.addEventListener(event, () => {
                    removeErrors(field);
                });
            });
        });
    };
    errors();

    let removeErrors = function (el) {
        let formGroup = el.closest('.group-form') ? el.closest('.group-form') : el.closest('.form-group');
        let invalidGroups = formGroup ? formGroup.querySelectorAll('.invalid-feedback') : [];
        if (el.classList.contains('is-invalid') || invalidGroups && invalidGroups.length > 0) {
            el.classList.remove('is-invalid');
            let invalid = el.closest('.is-invalid');
            if (invalid) {
                invalid.querySelectorAll('.invalid-feedback').forEach(feedback => {
                    feedback.remove();
                });
            }
            invalidGroups.forEach(function (group) {
                group.remove()
            });
            if (formGroup) {
                formGroup.querySelectorAll('.is-invalid').forEach(function (group) {
                    group.classList.remove('is-invalid');
                });
            }
        }
    }
}