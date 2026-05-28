/**
 * Newsletter form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import Modal from '../../../bootstrap/dist/modal';
import Cookies from 'js-cookie';
import {onSubmit} from "../../../../vendor/components/recaptcha";

import('../../../../../scss/front/default/components/form/_newsletter.scss');

/** Module-scope state shared across lazy-load invocations (inline + modal trigger the same module) */
let inputsReset = false;
let subscribedModals = new WeakSet();
let cookieDomain = window.location.host.replace('www.', '');
let cookieSecure = location.protocol !== "http:";
let baseCookieOptions = {path: '/', domain: cookieDomain, secure: cookieSecure};

export default function () {

    /** To display Modal */
    let showModal = function (modalEl, hide = false) {
        let cloneModal = modalEl.cloneNode(true);
        let modal = new Modal(cloneModal, {
            keyboard: false
        })
        modal.show();
        if (hide) {
            setTimeout(function () {
                modal.hide()
            }, 4500)
        }
    }

    /** Reset inputs */
    let resetInputs = function () {
        document.querySelectorAll('.newsletter-form-email').forEach(function (input) {
            input.setAttribute('value', '');
        });
        document.querySelectorAll('.external-input-email').forEach(function (input) {
            input.setAttribute('value', '');
        });
    }

    if (!inputsReset) {
        inputsReset = true;
        resetInputs();
    }

    /** Delayed newsletter modals (1 min wait, session cookie on close, 1-year cookie on subscribe) */
    let initDelayedModals = function () {
        document.querySelectorAll('.newsletter-modal[data-newsletter-modal-delay]').forEach(function (modalEl) {
            if (modalEl.dataset.newsletterModalBound === '1') {
                return;
            }
            modalEl.dataset.newsletterModalBound = '1';

            let cookieName = modalEl.dataset.newsletterModalCookie;
            if (cookieName && Cookies.get(cookieName)) {
                return;
            }

            let delay = parseInt(modalEl.dataset.newsletterModalDelay, 10) || 60000;
            let modal = new Modal(modalEl, {keyboard: true});

            setTimeout(function () {
                if (!cookieName || !Cookies.get(cookieName)) {
                    modal.show();
                }
            }, delay);

            modalEl.addEventListener('hidden.bs.modal', function () {
                if (!cookieName || subscribedModals.has(modalEl)) {
                    return;
                }
                Cookies.set(cookieName, '1', baseCookieOptions);
            });
        });
    }

    initDelayedModals();

    /** Events — idempotent per form to support multiple lazy-load invocations */
    let formsEvents = function () {
        document.querySelectorAll('.newsletter-form').forEach(function (form) {
            if (form.dataset.newsletterBound === '1') {
                return;
            }
            form.dataset.newsletterBound = '1';
            form.addEventListener('keydown', function (event) {
                if (event.key === "Enter") {
                    sendRequest(event, this);
                    return false;
                }
            });
        });
        document.querySelectorAll('.newsletter-submit').forEach(function (submit) {
            submit.onclick = function (event) {
                sendRequest(event, this.closest('form'));
            }
        });
    }

    formsEvents();

    function sendRequest(event, form) {

        event.preventDefault();

        import('../../../../vendor/components/recaptcha').then(({onSubmit: OnSubmit}) => {
            new OnSubmit(form);
        }).catch(error => console.error(error.message));

        let icon = form.querySelector('.newsletter-submit').querySelector('svg');
        let iconSpinner = form.querySelector('.spinner-border');
        let container = form.closest('.newsletter-form-container');
        let containerId = container.getAttribute('id');
        let modalEl = form.closest('.newsletter-modal');

        let beforeSend = function () {
            /** Remove errors */
            import('../../../../vendor/components/remove-errors').then(({default: removeErrors}) => {
                new removeErrors();
            }).catch(error => console.error(error.message));
            iconSpinner.classList.remove('d-none');
            icon.classList.add('d-none');
        }

        let xHttp = new XMLHttpRequest();
        xHttp.open("POST", form.getAttribute('action'), true);
        xHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        beforeSend();
        xHttp.send(serialize(form));
        xHttp.onload = function () {
            if (this.readyState === 4 && this.status === 200) {
                let response = JSON.parse(this.response)
                document.getElementById(containerId).outerHTML = response.html;
                formsEvents();
                import('../../../../vendor/components/keyup-fields').then(({default: keyupFields}) => {
                    new keyupFields();
                }).catch(error => console.error(error.message));
                if (response.success) {
                    resetInputs();
                    if (modalEl) {
                        let cookieName = modalEl.dataset.newsletterModalCookie;
                        if (cookieName) {
                            subscribedModals.add(modalEl);
                            Cookies.set(cookieName, '1', Object.assign({expires: 365}, baseCookieOptions));
                        }
                    }
                }
                if (response.success && response.redirection) {
                    document.location.href = response.redirection;
                }
            }
        }
    }

    /** Serialize form data */
    let serialize = function (form) {
        let serialized = []
        for (let i = 0; i < form.elements.length; i++) {
            let field = form.elements[i]
            if (!field.name || field.disabled || field.type === 'file' || field.type === 'reset' || field.type === 'submit' || field.type === 'button') continue
            if (field.type === 'select-multiple') {
                for (let n = 0; n < field.options.length; n++) {
                    if (!field.options[n].selected) continue
                    serialized.push(encodeURIComponent(field.name) + "=" + encodeURIComponent(field.options[n].value))
                }
            } else if ((field.type !== 'checkbox' && field.type !== 'radio') || field.checked) {
                serialized.push(encodeURIComponent(field.name) + "=" + encodeURIComponent(field.value))
            }
        }
        return serialized.join('&')
    }
}