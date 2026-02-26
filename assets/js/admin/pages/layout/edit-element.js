import Modal from '../../bootstrap/dist/modal';
import Tooltip from '../../bootstrap/dist/tooltip';

import resetModal from "../../../vendor/components/reset-modal";
import select2 from "../../../vendor/plugins/select2";
// import touchspin from "../../../vendor/plugins/touchspin";
import dropify from "../../form/dropify";

/**
 * On edit element btn click
 */
export default function (Routing) {

    /**
     * On input change in a new element form
     */
    let showSubmit = function () {
        document.body.addEventListener('change', function (e) {
            let formElement = e.target.closest('.edit-element-form');
            if (formElement && e.target.matches('input')) {
                let form = formElement.querySelector('form') || formElement.closest('form') || (formElement.tagName === 'FORM' ? formElement : null);
                if (form) {
                    let btn = form.querySelector('.modal-buttons button');
                    if (btn && btn.classList.contains('d-none')) {
                        btn.classList.remove('d-none');
                    }
                }
            }
        });
    };

    /**
     * On submitting
     */
    let submit = function () {
        document.body.addEventListener('click', function (e) {
            let submitBtn = e.target.closest('.edit-element-submit-btn');
            if (submitBtn) {
                e.preventDefault();
                let body = document.body;
                let modal = body.querySelector('.layout-modal');
                if (!body.classList.contains('ajax-posted')) {
                let form = submitBtn.closest('.edit-element-form');
                /** Refresh layout */
                import('./refresh-layout').then(({default: refreshLayout}) => {
                    new refreshLayout(Routing, form, modal, e);
                }).catch(error => console.error(error.message));
                }
            }
        });
    };

    /**
     * On adding Block
     */
    let addBlock = function () {
        document.body.addEventListener('click', function (e) {
            if (e.target.closest('.btn-add-block')) {
                let preloader = document.getElementById('main-preloader');
                if (preloader) {
                    preloader.classList.toggle('d-none');
                }
            }
        });
    };

    /**
     * Background modal
     */
    let backgroundModal = function () {
        document.body.addEventListener('change', function (e) {
            let input = e.target.closest('.background-rounded-selector input');
            if (input) {
                let elId = input.getAttribute('id');
                let body = document.body;
                body.querySelectorAll('.background-input-label-active').forEach(label => {
                    label.classList.remove('active');
                });
                let targetInput = body.querySelector('input#' + elId);
                if (targetInput) {
                    let label = targetInput.closest('.background-input-label-active');
                    if (label) {
                        label.classList.add('active');
                    }
                }
            }
        });
    };

    /**
     * Background modal
     */
    let copyClass = function () {
        document.body.addEventListener('click', function (e) {
            let copyBtn = e.target.closest('.class-copy');
            if (copyBtn) {
                let text = copyBtn.parentElement.querySelector('.text-copy').textContent;
                let modal = copyBtn.closest('.modal');
                if (modal) {
                    let field = modal.querySelector('.input-css');
                    if (field) {
                        let copy = field.value === "" ? text : field.value + " " + text;
                        field.value = copy;
                    }
                }
            }
        });
    };

    /**
     * Tabs height
     */
    let tabHeight = function () {
        document.body.addEventListener('shown.bs.modal', function (e) {
            let modal = e.target;
            let maxHeight = 0;
            let tabs = modal.querySelectorAll('.config-tabs-content .tab-pane-config');
            tabs.forEach(function (tab) {
                tab.classList.add("active");
                let height = tab.offsetHeight;
                maxHeight = (height > maxHeight ? height : maxHeight);
                if (!tab.classList.contains("show")) {
                    tab.classList.remove("active");
                }
            });
            tabs.forEach(function (tab) {
                tab.style.height = maxHeight + "px";
            });
        });
    };

    /**
     * Input label btn
     */
    let inputLabelBtn = function () {
        let body = document.body;
        body.addEventListener('change', function (e) {
            let inputBtn = e.target.closest('.input-btn');
            if (inputBtn) {
                let elId = inputBtn.getAttribute('id');
                body.querySelectorAll('.input-btn').forEach(input => {
                    let label = input.closest('label');
                    if (label) {
                        label.classList.remove('active');
                    }
                });
                let targetInput = body.querySelector('input#' + elId);
                if (targetInput) {
                    let label = targetInput.closest('label');
                    if (label) {
                        label.classList.add('active');
                    }
                }
            }
        });
    };

    /**
     * Show modal click handler
     */
    let showModal = function() {

        document.body.addEventListener('click', function handler(e) {

            let btn = e.target.closest('.edit-layout-element-btn');

            if (!btn) return;

            e.preventDefault();

            let body = document.body;
            let loader = body.querySelector('#layout-preloader');

            if (loader) {
                loader.classList.remove('d-none');
            }

            fetch(btn.dataset.path + "?ajax=true")
                .then(response => response.json())
                .then(response => {
                    let html = response.html;
                    let container = body.querySelector('#layout-grid');

                    if (container) {
                        container.insertAdjacentHTML('beforeend', html);
                    }

                    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                        Tooltip.getOrCreateInstance(el);
                    });

                    let modal = body.querySelector('.layout-modal:last-of-type');
                    if (modal) {
                        let modalId = modal.getAttribute('id');
                        let modalEl = document.getElementById(modalId);

                        if (modalEl) {
                            Modal.getOrCreateInstance(modalEl).show();
                            if (loader) {
                                loader.classList.add('d-none');
                            }

                            select2();
                            dropify();
                            // touchspin();

                            let layoutPreloader = document.getElementById("layout-preloader");
                            if (layoutPreloader) {
                                layoutPreloader.classList.add('d-none');
                            }

                            modalEl.querySelectorAll('[data-bs-toggle="preloader"]').forEach(preloaderBtn => {
                                preloaderBtn.addEventListener('click', function () {
                                    let mainPreloader = document.getElementById("main-preloader");
                                    if (mainPreloader) {
                                        mainPreloader.classList.toggle('d-none');
                                    }
                                    let referPreloader = this.closest('.refer-preloader');
                                    let stripePreloader = referPreloader ? referPreloader.querySelector('.stripe-preloader') : null;
                                    let preloader = stripePreloader || document.getElementById("layout-preloader");
                                    if (preloader) {
                                        preloader.classList.remove('d-none');
                                    }
                                });
                            });

                            import('../../form/btn-group-toggle').then(({default: btnToggle}) => {
                                new btnToggle();
                            }).catch(error => console.error(error.message));

                            let colorPicker = body.querySelector('.colorpicker');
                            if (colorPicker) {
                                import('./../../plugins/colorpicker').then(({default: asColorPicker}) => {
                                    new asColorPicker();
                                }).catch(error => console.error(error.message));
                            }

                            modalEl.addEventListener('click', function (e) {
                                let resetBtn = e.target.closest('.reset-margins');
                                if (resetBtn) {
                                    e.preventDefault();
                                    import('./../../plugins/sweet-alert').then(({default: sweetAlert}) => {
                                        new sweetAlert(e, resetBtn);
                                    }).catch(error => console.error(error.message));
                                }
                            });

                            modalEl.addEventListener('hide.bs.modal', function () {
                                resetModal(modalEl, true);
                                document.querySelectorAll('.modal-wrapper').forEach(wrapper => wrapper.remove());
                            });
                        }
                    }
                })
                .catch(errors => {
                    let modal = body.querySelector('.modal');

                    /** Display errors */
                    import('../../core/errors').then(({default: displayErrors}) => {
                        new displayErrors(errors);
                    }).catch(error => console.error(error.message));

                    if (modal) {
                        resetModal(modal, true);
                    }
                });
        });
    };

    if (!window.editElementInitialized) {
        showSubmit();
        submit();
        backgroundModal();
        copyClass();
        tabHeight();
        addBlock();
        inputLabelBtn();
        showModal();
        window.editElementInitialized = true;
    }
}