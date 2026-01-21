/** Import CSS */
import '../../../../scss/front/default/templates/security.scss';

/** Import JS */

const form = document.querySelector('form');
if (form) {
    import('../components/form/form').then(({default: Form}) => {
        new Form();
    }).catch(error => console.error(error.message));
}

document.querySelectorAll('form.security:not(.form-ajax)').forEach((form) => {
    window.addEventListener('submit', function () {
        import('../../../vendor/components/recaptcha').then(({onSubmit: OnSubmit}) => {
            new OnSubmit(form);
        }).catch(error => console.error(error.message));
    });
});