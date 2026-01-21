/**
 * Newsletter form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import Modal from '../../../bootstrap/dist/modal';
import {onSubmit} from "../../../../vendor/components/recaptcha";

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

    resetInputs();

    /** Events */
    let formsEvents = function () {
        document.querySelectorAll('.newsletter-form').forEach(function (form) {
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
        let containerId = form.closest('.newsletter-form-container').getAttribute('id');

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