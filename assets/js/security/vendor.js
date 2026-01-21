import '../../scss/security/vendor.scss';

/**
 * Security Vendor
 *
 * @copyright 2020
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @version 1.0
 * @licence under the MIT License (LICENSE.txt)
 *
 *  1 - Preloader
 *  2 - Lazy load
 *  3 - Password field
 *  4 - Recaptcha
 */

/** 1 - Preloader */
import './preloader';

/** 2 - Lazy load */
import(/* webpackPreload: true */ '../vendor/components/lazy-load').then(({default: lazyLoad}) => {
    new lazyLoad();
}).catch(error => console.error(error.message));

/** 3 - Password field */
import passwordFields from '../vendor/components/password-field';
let fields = document.querySelectorAll('.show-password');
if (fields.length > 0) {
    passwordFields(fields);
}

const inputPwd = document.querySelector('.password-checker');
if (inputPwd) {
    import('../vendor/components/password-checker').then(({default: Checker}) => {
        new Checker(inputPwd);
    }).catch(error => console.error(error.message));
}

/** 4 - Recaptcha */
let formSecurity = document.querySelectorAll('form.security')
if (formSecurity.length > 0) {
    import('../vendor/components/recaptcha').then(({generate: Generate}) => {
        new Generate();
    }).catch(error => console.error(error.message));
}

document.querySelectorAll('form.security').forEach(function (form) {
    let submit = form.querySelector('[type="submit"]');
    submit.onclick = function () {
        import(/* webpackPreload: true */ '../vendor/components/recaptcha').then(({onSubmit: OnSubmit}) => {
            new OnSubmit(form);
        }).catch(error => console.error(error.message));
    }
});

window.addEventListener('load', () => {

    let filled = function(input) {
        if (input.value !== '') {
            input.classList.add('filled');
            input.parentNode.classList.add('filled-group');
        } else {
            input.parentNode.classList.remove('filled');
            input.parentNode.classList.remove('filled-group');
        }
    }

    let fieldsForm = document.querySelectorAll('input.material');
    fieldsForm.forEach(input => {
        filled(input);
        input.addEventListener('change', () => {
            filled(input);
        });
    });
});