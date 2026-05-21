import '../../scss/security/vendor.scss';

/**
 * Security Vendor
 *
 * @copyright 2026
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

document.querySelectorAll('.form-control').forEach((el) => {
    const toggleFocusClass = (isFocused) => {
        const inputGroup = el.closest('.input-group');
        const floatingGroup = el.closest('.form-floating');
        const group = inputGroup ? inputGroup : (floatingGroup ? floatingGroup : el.closest('.group-form'));
        if (!group) {
            return;
        }
        group.classList.toggle('focus-group', isFocused);
    };
    el.addEventListener('focusin', () => toggleFocusClass(true));
    el.addEventListener('focusout', () => toggleFocusClass(false));
    if (document.activeElement === el) {
        toggleFocusClass(true);
    }
});

document.querySelectorAll('link.preload-css[rel="preload"]').forEach(link => {
    link.rel = 'stylesheet';
});

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

/** Bootstrap Tab — loaded only when a tab trigger exists on the page */
const tabTriggers = document.querySelectorAll('[data-bs-toggle="tab"]');
if (tabTriggers.length > 0) {
    import('bootstrap/js/dist/tab').then(({default: Tab}) => {
        tabTriggers.forEach(el => new Tab(el));
    }).catch(error => console.error(error.message));
}

/** Generic copy-to-clipboard for [data-copy-target] buttons */
document.querySelectorAll('[data-copy-target]').forEach(btn => {
    const label = btn.querySelector('span');
    const original = label ? label.textContent : null;
    const done = btn.dataset.copyLabelDone || 'Copied';

    btn.addEventListener('click', () => {
        const target = document.querySelector(btn.dataset.copyTarget);
        if (!target) return;
        const value = ('value' in target) ? target.value : target.textContent;
        navigator.clipboard.writeText(value).then(() => {
            if (label) label.textContent = done;
            btn.classList.add('copied');
            setTimeout(() => {
                if (label && original !== null) label.textContent = original;
                btn.classList.remove('copied');
            }, 1500);
        }).catch(error => console.error(error.message));
    });
});

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

    let filled = function (input) {
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