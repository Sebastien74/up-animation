import '../../../../../scss/front/default/components/form/_steps-form.scss';

import {hideLoader, displayLoader} from "../loader";
import removeErrors from '../../../../vendor/components/remove-errors';
import {scrollToEL} from "../../functions";

/**
 * Form Steps
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    function handleChange(event) {
        const input = event.currentTarget;
        const formContainer = input.closest('.steps-form-container');
        const form = input.closest('form');
        const field = event.target;
        if (!field.classList.contains('dynamic-in-progress')) {
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
                    const form = html.querySelector('.step-form-ajax');
                    const formId = form.getAttribute('id');
                    let container = document.getElementById(formId).closest('.steps-form-container');
                    if (form) {
                        container.innerHTML = form.closest('.steps-form-container').innerHTML;
                    }
                    if (container) {
                        container.querySelectorAll('.group-form.is-invalid').forEach(function (group) {
                            group.classList.remove('is-invalid');
                            group.querySelectorAll('.is-invalid').forEach(function (el) {
                                el.classList.remove('is-invalid');
                            });
                            group.querySelectorAll('.invalid-feedback').forEach(function (el) {
                                el.remove();
                            });
                        });
                    }
                    hideLoader(container);
                    dynamicChange();
                    post();
                    import('./form-package').then(({default: FormPackage}) => {
                        new FormPackage()
                    }).catch(error => console.error(error.message));
                }
            }
        }
    }

    const dynamicChange = function () {
        document.querySelectorAll('.step-form-ajax').forEach(function (form) {
            form.querySelectorAll('.dynamic-step-field').forEach(function (field) {
                field.addEventListener('change', handleChange);
                field.addEventListener('input', handleChange);
            });
        });
    }
    dynamicChange();

    const tabsAdvanced = function (element, step) {
        element.querySelectorAll('.step-tab').forEach(function (stepTab) {
            const stepTabId = stepTab.dataset.step;
            if (stepTabId <= step && !stepTab.classList.contains('done')) {
                stepTab.classList.add('done');
            } else if (stepTabId > step) {
                stepTab.classList.remove('done');
            }
        });
    };

    const previousStep = function (container) {
        container.querySelectorAll('.btn-previous').forEach(function (btn) {
            btn.onclick = function (e) {
                e.preventDefault();
                const previous = btn.dataset.previous;
                const previousEl = container.querySelector('.step-form-container[data-step="' + previous + '"]');
                if (previousEl) {
                    container.querySelectorAll('.step-form-container').forEach(function (step) {
                        step.classList.add('d-none');
                    });
                    previousEl.classList.remove('d-none');
                    scrollToEL(previousEl);
                    tabsAdvanced(container, previous);
                }
            }
        });
    };

    const post = function () {

        document.querySelectorAll('.step-form-ajax').forEach(function (form) {

            form.querySelectorAll('[type="submit"]').forEach(function (submitBtn) {

                submitBtn.onclick = function (e) {

                    e.preventDefault();

                    const formId = form.getAttribute('id');
                    const currentStep = submitBtn.dataset.step;
                    const next = submitBtn.dataset.next;
                    let redirection = form.dataset.redirection;
                    let container = submitBtn.closest('.steps-form-container');

                    import('../../../../vendor/components/recaptcha').then(({onSubmit: OnSubmit}) => {
                        new OnSubmit(form);
                    }).catch(error => console.error(error.message));

                    let xHttp = new XMLHttpRequest();
                    xHttp.open("POST", form.getAttribute('action') + '?advancement=' + submitBtn.dataset.advancement, true);
                    displayLoader(container);
                    removeErrors();
                    xHttp.send(new FormData(form));
                    xHttp.onload = function () {
                        if (this.readyState === 4 && this.status === 200) {

                            let response = this.response;
                            response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
                            response = JSON.parse(response);
                            redirection = typeof redirection != 'undefined' && redirection ? redirection : (typeof response.redirection != 'undefined' && response.redirection ? response.redirection : null);
                            const hasSuccess = response.success && submitBtn.classList.contains('last');

                            let html = document.createElement('div');
                            html.innerHTML = response.html;
                            const form = html.querySelector('.step-form-ajax');

                            const container = document.getElementById(formId).closest('.steps-form-container');
                            if (container && form) {
                                container.innerHTML = form.closest('.steps-form-container').innerHTML;
                            }

                            const stepContainers = container.querySelectorAll('.step-form-container');

                            stepContainers.forEach(function (step) {
                                step.classList.add('d-none');
                            });

                            if (hasSuccess) {

                                container.querySelector('div.step-form-container[data-step="' + currentStep + '"]').classList.remove('d-none');
                                document.location.href = redirection;

                            } else {

                                stepContainers.forEach(function (step) {
                                    const stepId = step.dataset.step;
                                    const invalids = step.querySelectorAll('.invalid-feedback');
                                    if (invalids.length > 0 && currentStep !== stepId) {
                                        step.querySelectorAll('.invalid-feedback').forEach(function (el) {
                                            el.remove();
                                        });
                                        step.querySelectorAll('.is-invalid').forEach(function (el) {
                                            el.classList.remove('is-invalid');
                                        });
                                    }
                                    if (invalids.length === 0 && currentStep === stepId) {
                                        container.querySelector('div.step-form-container[data-step="' + next + '"]').classList.remove('d-none');
                                        tabsAdvanced(container, next);
                                    } else if (invalids.length > 0 && currentStep === stepId) {
                                        step.classList.remove('d-none');
                                    }
                                });

                                hideLoader(container);
                                post();
                                dynamicChange();
                                previousStep(container);

                                import('./form-package').then(({default: FormPackage}) => {
                                    new FormPackage()
                                }).catch(error => console.error(error.message));
                            }
                        }
                    }
                }
            });
        });
    }
    post();
}