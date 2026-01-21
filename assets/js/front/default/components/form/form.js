/**
 * Form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import scrollToEl from '../../../../vendor/components/scroll-to';
import removeErrors from '../../../../vendor/components/remove-errors';
import {hideLoader, displayLoader} from "../loader";
import Modal from '../../../bootstrap/dist/modal';

export default function () {

    let forms = document.querySelectorAll('form:not(.own-js)');
    if (forms.length > 0) {
        import('./form-package').then(({default: FormPackage}) => {
            new FormPackage();
        }).catch(error => console.error(error.message));
    }

    /** On focus */
    let onFocus = function (form) {
        const autofill = function (input, group) {
            if (input.value) {
                group.classList.add('autofill');
            } else {
                group.classList.remove('autofill');
            }
        }
        if (form) {
            const elClasses = ['form-group', 'form-floating', 'input-group', 'group-form'];
            form.querySelectorAll('.form-control').forEach((input) => {
                input = document.getElementById(input.getAttribute('id'));
                if (input) {
                    elClasses.forEach((elClass) => {
                        let group = input.closest('.' + elClass);
                        if (group) {
                            autofill(input, group);
                            input.addEventListener('focus', () => {
                                group.classList.add('focus');
                            });
                            input.addEventListener('blur', () => {
                                group.classList.remove('focus');
                                autofill(input, group);
                            });
                            input.addEventListener('change', () => {
                                autofill(input, group);
                            });
                        }
                    });
                }
            });
        }
    }

    document.querySelectorAll('.img-drop').forEach((input) => {
        input.addEventListener('change', () => {
            const fileReader = new FileReader();
            const file = input.files;
            fileReader.onload = event => {
                let wrap = input.closest('.img-drop-wrap');
                let preview = wrap.querySelector('.drop-preview');
                let smallModify = wrap.querySelector('.modify');
                let fullModify = wrap.querySelector('.full-modify');
                preview.innerHTML = '<img src="' + event.target.result + '" alt="Preview" class="img-fluid" />';
                if (!wrap.classList.contains('have-media')) {
                    wrap.classList.add('have-media');
                }
                if (fullModify) {
                    fullModify.classList.add('d-none');
                }
                if (smallModify) {
                    smallModify.classList.remove('d-none');
                }
            }
            fileReader.readAsDataURL(file[0]);
        });
    });

    forms.forEach(form => {
        onFocus(form);
        /** Forms reset */
        const resetForm = !(form.dataset.reset && parseInt(form.dataset.reset) === 0);
        if (resetForm) {
            form.reset();
        }
        /** Remove loader */
        const formContainer = form.closest('.form-container');
        if (formContainer) {
            hideLoader(formContainer);
        }
        /** Scroll to alert */
        const alert = form.querySelector('.alert.alert-danger:not(.d-none)');
        if (alert && !alert.parentNode.classList.contains('d-none')) {
            scrollToEl(alert);
        }
    });

    let showModal = function (modalEl, hide = false) {
        let cloneModal = modalEl.cloneNode(true);
        let modal = new Modal(cloneModal, {
            keyboard: false
        });
        modal.show();
        if (hide) {
            setTimeout(function () {
                modal.hide();
            }, 4500);
        }
    }

    /** To display thanks modal */
    let modals = document.querySelectorAll('.thanks-modal.show');
    modals.forEach((modal) => {
        showModal(modal);
    });

    /** Set filename on input file change */
    let fileChange = function () {

        document.querySelectorAll('input[type="file"]').forEach((input) => {

            input.addEventListener('change', (event) => {

                const group = input.closest('.file-group');

                group.querySelectorAll('.invalid-feedback').forEach(function (error) {
                    error.remove();
                });

                const fileNames = [...event.target.files]
                    .map(file => file.name)
                    .join(', ');

                [...event.target.files].forEach((file) => {
                    const maxSize = parseInt(input.dataset.maxSize);
                    let fileSizeInKB = file.size / 1024; // Convert file size to kilobytes
                    fileSizeInKB = fileSizeInKB.toFixed(0); // Optional: round to 0 decimal places
                    if (maxSize < fileSizeInKB) {
                        const htmlMessage = document.createElement('div');
                        htmlMessage.classList.add('invalid-feedback', 'd-block');
                        htmlMessage.innerText = input.dataset.maxSizeMessage.replace('%name%', file.name);
                        group.appendChild(htmlMessage);
                    }
                });

                let label = group.querySelector('.custom-file-label');
                input.setAttribute('placeholder', fileNames);
                if (label && fileNames) {
                    label.innerHTML = fileNames;
                }

                let removeFile = group.querySelector('.remove-file-btn');
                if (removeFile) {
                    removeFile.classList.add('show');
                    removeFile.addEventListener('click', e => {
                        input.value = null;
                        removeFile.classList.remove('show');
                        label.innerHTML = "";
                    });
                }
            })
        });
    }
    fileChange();

    /** Choices dynamic Form */
    let dynamicChange = function () {

        /** To remove empty form group block */
        let removeBlocks = function (form) {
            if (form) {
                const identifiers = ['.form-group', '.text-block', '.title-block'];
                form.querySelectorAll('.layout-block').forEach(function (block) {
                    const hasRequiredChild = identifiers.some(selector => block.querySelector(selector));
                    if (!hasRequiredChild) {
                        block.remove();
                    }
                });
            }
        }

        document.querySelectorAll('.dynamic-field').forEach(function (field) {

            let formContainer = field.closest('.form-container');
            let form = field.closest('form');

            removeBlocks(form);

            /** To refresh form on change */
            field.addEventListener('change', () => {

                /** To remove associated values */
                let elementIds = typeof field.dataset.elements !== 'undefined' ? JSON.parse(field.dataset.elements) : [];
                for (let j = 0; j < elementIds.length; j++) {
                    let element = form.querySelector('[name="front_form[field_' + elementIds[j] + ']"]');
                    if (element) {
                        let block = element.closest('.layout-block');
                        block.classList.add('d-none');
                        element.value = null;
                        let subElementIds = typeof element.dataset.elements !== 'undefined' ? JSON.parse(element.dataset.elements) : [];
                        for (let k = 0; k < subElementIds.length; k++) {
                            let subElement = form.querySelector('[name="front_form[field_' + subElementIds[k] + ']"]');
                            if (subElement) {
                                let block = subElement.closest('.layout-block');
                                block.classList.add('d-none');
                                subElement.value = null;
                            }
                        }
                    }
                }

                /** Change request */
                displayLoader(formContainer);
                const xHttp = new XMLHttpRequest();
                const action = form.getAttribute('action');
                const refreshAction = action.indexOf('?') > -1 ? action + '&refresh=true' : action + '?refresh=true';
                xHttp.open("POST", refreshAction, true);
                xHttp.send(new FormData(form));
                xHttp.onload = function () {

                    if (this.readyState === 4 && this.status === 200) {

                        let response = this.response;
                        response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
                        response = JSON.parse(response);
                        let html = document.createElement('div');
                        html.innerHTML = response.html;

                        const form = html.querySelector('.form-ajax');
                        const formId = form.getAttribute('id');
                        let container = document.getElementById(formId).closest('.form-container');

                        if (form) {
                            container.innerHTML = form.closest('.form-container').innerHTML;
                            let alertHTML = container.querySelector('.form-alert');
                            if (alertHTML) {
                                let submit = document.getElementById(formId).querySelector('button[type="submit"]');
                                let parent = submit.parentNode;
                                let alert = document.createElement("div");
                                alert.classList.add('alert', 'alert-danger');
                                alert.innerHTML = alertHTML.innerHTML;
                                parent.insertBefore(alert, submit);
                                alertHTML.remove();
                            }
                            removeBlocks(form);
                        }

                        hideLoader(container);

                        container.querySelectorAll('select').forEach(function (select) {
                            if (select.value) {
                                select.classList.add('selected');
                            }
                        });

                        /** Form packages */
                        import('./form-package').then(({default: formPackage}) => {
                            new formPackage()
                        }).catch(error => console.error(error.message));

                        /** Scroll to alert */
                        let alertEl = form.querySelector('.alert');
                        if (alertEl) {
                            scrollToEl(alertEl, false);
                        }

                        removeErrors();
                        dynamicChange();
                        post();
                        fileChange();
                        onFocus(form);

                        const successCard = document.querySelector('.form-success-card');
                        if (successCard) {
                            scrollToEl(successCard);
                        }
                    }
                }
            })
        });
    }
    dynamicChange();

    /** For hidden zones as a step */
    let formHiddenZones = function (btn, container, classname) {

        let hasHidden = container.querySelectorAll('.' + classname + '.d-none');

        if (hasHidden.length > 0) {

            let zones = container.getElementsByClassName(classname);

            if (zones.length > 0) {

                let currentZoneId = btn.closest('.' + classname).getAttribute('id');
                let zoneLength = zones.length;
                let zoneInit = false;

                for (let i = 0; i < zones.length; i++) {

                    let index = i + 1;
                    let zone = zones[i];
                    let invalidFields = zone.querySelectorAll('.is-invalid');
                    let invalidFeedbacks = zone.querySelectorAll('.invalid-feedback');
                    let haveInvalidFields = invalidFields.length > 0;
                    let zoneId = zone.getAttribute('id');

                    if (!zoneInit && haveInvalidFields && zoneId !== currentZoneId) {
                        zoneInit = true;
                        zone.classList.remove('d-none');
                        invalidFields.forEach((invalidField) => {
                            if (typeof invalidField != 'undefined') {
                                invalidField.classList.remove('is-invalid');
                                invalidField.classList.add('is-valid-in-js');
                            }
                        });
                        invalidFeedbacks.forEach((invalidFeedback) => {
                            invalidFeedback.classList.add('d-none');
                        });
                        window.scrollTo({top: zone.offsetTop - 50, behavior: 'smooth'});
                    } else if (haveInvalidFields && zoneId === currentZoneId) {
                        zoneInit = true;
                        if (zone.classList.contains('d-none')) {
                            zone.classList.remove('d-none');
                        }
                    } else if (!zoneInit && index < zoneLength && !zone.classList.contains('d-none')) {
                        zone.classList.add('d-none');
                    } else if (!zoneInit && index === zoneLength) {
                        zone.classList.remove('d-none');
                    }
                }
            }
        }
    }

    /** Form refresh */
    let refresh = function (event, submitBtn) {

        let alertBlock = document.getElementById('alert-form-block');
        if (alertBlock) {
            alertBlock.classList.add('d-none');
        }

        document.querySelectorAll('.alert-success').forEach(function (alert) {
            alert.remove();
        });

        let container = submitBtn.closest('.form-container');
        let validFiles = checkInputsFiles(submitBtn);
        let form = submitBtn.closest('form');

        if (!validFiles) {
            event.preventDefault();
            hideLoader(container);
            return true
        } else if (validFiles && !form.classList.contains('form-ajax')) {
            // form.unbind('submit').submit();
        }

        if (!submitBtn.classList.contains('form-ajax')) {
            displayLoader(container);
        }
    }

    /** Post process */
    let post = function () {

        /** On form submit */
        document.querySelectorAll('form:not(.own-js) [type="submit"]').forEach(function (submitBtn) {
            submitBtn.onclick = function (event) {
                refresh(event, submitBtn);
            }
        });

        /** On Ajax form submitted */
        document.querySelectorAll('.form-ajax:not(.own-js) [type="submit"]').forEach(function (ajaxSubmitBtn) {

            ajaxSubmitBtn.onclick = function (event) {

                event.preventDefault();

                refresh(event, ajaxSubmitBtn);

                let container = ajaxSubmitBtn.closest('.form-container');
                let form = ajaxSubmitBtn.closest('form');
                let formId = form.getAttribute('id');
                let redirection = form.dataset.redirection;
                let resetForm = !(form.dataset.reset && parseInt(form.dataset.reset) === 0);
                let formCalendars = document.querySelectorAll('[data-component="form-calendar"]');

                import('../../../../vendor/components/recaptcha').then(({onSubmit: OnSubmit}) => {
                    new OnSubmit(form);
                }).catch(error => console.error(error.message));

                setTimeout(function () {
                    let xHttp = new XMLHttpRequest();
                    xHttp.open("POST", form.getAttribute('action'), true);
                    displayLoader(container);
                    beforeSend();
                    xHttp.send(new FormData(form));
                    xHttp.onload = function (e) {
                        if (this.readyState === 4 && this.status === 200) {

                            let response = this.response;
                            response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
                            response = JSON.parse(response);
                            redirection = typeof redirection != 'undefined' && redirection ? redirection : (typeof response.redirection != 'undefined' && response.redirection ? response.redirection : null);

                            if (response.success && redirection && !response.showModal) {
                                if (typeof response.token != 'undefined' && response.token) {
                                    document.location.href = redirection + '?token=' + response.token;
                                } else {
                                    document.location.href = redirection;
                                }
                            } else {

                                let html = document.createElement('div');
                                html.innerHTML = response.html;
                                let form = html.querySelector('.form-ajax');
                                let container = document.getElementById(formId).closest('.form-container');

                                if (form) {
                                    container.innerHTML = form.closest('.form-container').innerHTML;
                                }
                                let formEl = document.getElementById(formId);

                                fileChange();
                                onFocus(form);

                                let stepBlocks = html.getElementsByClassName('step-block');
                                if (stepBlocks) {
                                    formHiddenZones(ajaxSubmitBtn, container, 'step-block');
                                } else {
                                    formHiddenZones(ajaxSubmitBtn, container, 'layout-zone');
                                }

                                hideLoader(container);

                                if (response.success && resetForm) {

                                    document.getElementById(formId).querySelectorAll('.form-control').forEach(function (input) {
                                        if (input.type && input.type === 'checkbox') {
                                            input.checked = false;
                                        } else {
                                            input.value = "";
                                        }
                                    });

                                    document.getElementById(formId).querySelectorAll('.form-check-input').forEach(function (input) {
                                        if (input.type && input.type === 'checkbox') {
                                            input.checked = false;
                                        }
                                    });

                                    // if(!response.showModal) {
                                    //     window.location.replace(window.location.href + '?form=success');
                                    // }
                                }

                                if (form && response.dataId) {
                                    form.setAttribute('data-custom-id', response.dataId);
                                }

                                if (formCalendars.length > 0) {
                                    import('./form-calendar').then(({default: formCalendarRefreshModule}) => {
                                        new formCalendarRefreshModule(e, form);
                                    }).catch(error => console.error(error.message));
                                }

                                if (response.success && response.showModal && form) {
                                    let modalEl = document.getElementById(form.dataset.modal);
                                    showModal(modalEl, true);
                                    setTimeout(function () {
                                        if (redirection) {
                                            document.location.href = redirection;
                                        }
                                    }, 4500);
                                } else if (!response.success && response.message) {
                                    let alert = '<div class="alert alert-danger">' + response.message + '</div>';
                                    document.getElementById(formId).append(alert);
                                }

                                /** Form packages */
                                import('./form-package').then(({default: formPackage}) => {
                                    new formPackage();
                                }).catch(error => console.error(error.message));

                                /** Scroll to alert */
                                let alert = formEl ? formEl.querySelector('.alert') : null;
                                if (alert) {
                                    setTimeout(function () {
                                        scrollToEl(alert);
                                    }, 50);
                                }

                                post();
                                inputsRefreshChange();
                                dynamicChange();
                            }
                        }
                    }
                });
            }
        });

        let beforeSend = function () {
            removeErrors();
        }
    }

    post();

    /** Multiple files validation */
    let checkInputsFiles = function (el) {
        let isValid = true;
        const form = el.closest('form');
        form.querySelectorAll('input[type="file"][multiple="multiple"]').forEach(function (input) {
            let isRequired = input.hasAttribute('required');
            if (isRequired && !input.value || isRequired && input.type === 'checkbox' && !input.checked) {
                isValid = false;
                document.getElementById('alert-form-block').classList.remove('d-none');
            }
        });
        return isValid;
    }

    /** On inputs refresh change */
    let inputsRefreshChange = function () {
        document.querySelectorAll('.input-refresh').forEach(function (input) {
            let form = input.closest('form');
            let formId = form.getAttribute('id');
            let container = input.closest('.form-container');
            input.addEventListener('change', function () {
                let beforeSend = function () {
                    removeErrors();
                }
                let xHttp = new XMLHttpRequest()
                xHttp.open("POST", form.getAttribute('action') + '?update_form=true', true);
                displayLoader(container);
                beforeSend();
                xHttp.send(new FormData(form));
                xHttp.onload = function () {
                    if (this.readyState === 4 && this.status === 200) {
                        let response = JSON.parse(this.response);
                        let html = document.createElement('div');
                        html.innerHTML = response.html;
                        let form = html.getElementsByClassName('form-ajax')[0];
                        let container = document.getElementById(formId).closest('.form-container');
                        container.innerHTML = form.closest('.form-container').innerHTML;
                        hideLoader(container);
                        import('./form-package').then(({default: formPackage}) => {
                            new formPackage();
                        }).catch(error => console.error(error.message));
                        removeErrors();
                        inputsRefreshChange();
                        post();
                    }
                }
            });
        });
    }
    inputsRefreshChange();
}