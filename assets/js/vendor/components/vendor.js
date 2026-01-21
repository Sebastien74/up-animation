/**
 * Core vendor
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 *  1 - Decrypt phone
 *  2 - AI
 *  3 - Recaptcha
 *  4 - Waves effect
 *  5 - Form
 *  6 - Copy
 */

let body = document.body

/** 1 - Decrypt */
import './decrypt'

window.addEventListener("load", function () {

    /** 2 - AI */
    let aiBot = body.querySelectorAll('.btn-chatgpt');
    if (aiBot) {
        import('./ai').then(({default: Ai}) => {
            new Ai();
        }).catch(error => console.error(error.message));
    }

    /** 3 - Recaptcha */
    let formSecurity = body.querySelectorAll('form.security')
    if (formSecurity.length > 0) {
        import('./recaptcha').then(({default: recaptcha}) => {
            new recaptcha();
        }).catch(error => console.error(error.message));
    }
});

/** 4 - Waves effect */
let waves = body.querySelectorAll('.waves-effect')
if (waves.length > 0) {
    import('../libraries/waves').then(({default: waves}) => {
        new waves();
    }).catch(error => console.error(error.message));
}

/** 5 - Form */
let forms = body.querySelectorAll('form')
if (forms.length > 0) {
    /** Form custom fields */
    import('./form').then(({default: form}) => {
        new form();
    }).catch(error => console.error(error.message));
    /** Keyup */
    import('./keyup-fields').then(({default: keyupForm}) => {
        new keyupForm();
    }).catch(error => console.error(error.message));
}

/** 6 - Copy */
import('./copy').then(({default: copy}) => {
    new copy();
}).catch(error => console.error(error.message));