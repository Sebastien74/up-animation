import ajaxRowProcess from "./ajax-row";
import {refreshTinymce, tinymcePlugin} from "../plugins/tinymce";

/**
 * Ajax Form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let body = document.body;
    let clicks = function () {
        let buttons = document.querySelectorAll('.ajax-post');
        buttons.forEach(function (btn) {
            btn.onclick = function (e) {
                e.preventDefault();
                refreshTinymce();
                let referPreloader = btn.closest('.refer-preloader');
                let parentPreloader = btn.closest('.parent-preloader');
                let stripePreloader = referPreloader ? referPreloader.querySelector('.stripe-preloader') : null;
                let loader = stripePreloader ? stripePreloader : body.querySelector('.main-preloader');
                if (btn.classList.contains('inner-preloader-btn')) {
                    loader = parentPreloader ? parentPreloader.querySelector('.inner-preloader') : body.querySelector('.inner-preloader');
                }
                let asMedias = btn.classList.contains('medias');
                let tab = asMedias ? btn.closest('.tab-content') : null;
                import('../../vendor/components/remove-errors').then(({default: removeErrors}) => {
                    new removeErrors();
                }).catch(error => console.error(error.message));
                loader.classList.remove('d-none');
                if (tab) {
                    let forms = tab.querySelectorAll('form');
                    forms.forEach(function (form) {
                        post(form, btn, loader);
                    });
                } else {
                    let form = btn.closest('form');
                    post(form, btn, loader);
                }
            }
        });
    }
    clicks();

    let post = function (form, btn, loader) {
        let formId = form.getAttribute('id');
        let action = form.getAttribute('action');
        let url = action + '?ajax=true';
        if (action.indexOf('?') > -1) {
            url = action + "&ajax=true";
        }
        let xHttp = new XMLHttpRequest()
        xHttp.open("POST", url, true)
        // xHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded")
        xHttp.send(new FormData(document.getElementById(formId)))
        xHttp.onload = function (e) {
            if (this.readyState === 4 && this.status === 200) {

                let asRefresh = btn.classList.contains('refresh');

                let response = this.response;
                response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
                response = JSON.parse(response);

                if (response.flashBag) {
                    loader.addClass('d-none');
                } else if (asRefresh && response.success && response.redirection) {
                    window.location.href = response.redirection;
                } else if (asRefresh && response.success) {
                    location.reload();
                } else if (response.html) {

                    let removeModal = btn.classList.contains('remove-modal');
                    let closeModal = btn.classList.contains('close-modal');
                    let html = document.createElement('div');
                    html.innerHTML = response.html;

                    if (response.success && removeModal) {
                        /** Reset modal */
                        let modal = form.closest('.modal');
                        import('../../vendor/components/reset-modal').then(({default: resetModal}) => {
                            new resetModal(modal, true);
                        }).catch(error => console.error(error.message));
                    }

                    if (response.success && closeModal) {
                        /** Reset modal */
                        let modal = form.closest('.modal');
                        import('../../vendor/components/reset-modal').then(({default: resetModal}) => {
                            new resetModal(modal);
                        }).catch(error => console.error(error.message));
                    }

                    let ajaxContent = form.querySelector(".ajax-content");
                    if (!ajaxContent) {
                        ajaxContent = form.closest(".ajax-content");
                    }
                    let responseContent = html.innerHTML;
                    let responseHtml = html.querySelector('.ajax-html');
                    if (responseHtml) {
                        responseContent = responseHtml.innerHTML;
                    }
                    ajaxContent.innerHTML = responseContent;

                    /** Refresh dropify */
                    import('./dropify').then(({default: dropifyJS}) => {
                        new dropifyJS();
                    }).catch(error => console.error(error.message));

                    /** Refresh select2 */
                    import('../../vendor/plugins/select2').then(({default: select2}) => {
                        new select2();
                    }).catch(error => console.error(error.message));

                    import('./../form/btn-group-toggle').then(({default: btnToggle}) => {
                        new btnToggle();
                    }).catch(error => console.error(error.message));

                    import('../../vendor/plugins/touchspin').then(({default: touchSpin}) => {
                        new touchSpin();
                    }).catch(error => console.error(error.message));

                    /** Tinymce */
                    tinymcePlugin();

                    /** Scroll to errors */
                    import('../../vendor/components/scroll-error').then(({default: scrollErrors}) => {
                        new scrollErrors();
                    }).catch(error => console.error(error.message));

                    if (response.success && closeModal) {
                        /** Reset modal */
                        let modal = form.closest('.modal');
                        import('../../vendor/components/reset-modal').then(({default: resetModal}) => {
                            new resetModal(modal);
                        }).catch(error => console.error(error.message));
                        form[0].reset();
                        loader.classList.add('d-none');
                    }

                    if (response.success && removeModal) {
                        /** Reset modal */
                        let modal = form.closest('.modal');
                        import('../../vendor/components/reset-modal').then(({default: resetModal}) => {
                            new resetModal(modal, true);
                        }).catch(error => console.error(error.message));
                    }

                    loader.classList.add('d-none');
                    clicks();
                }
            }
        }
    };

    /** Set filename on input file change */
    let fileChange = function () {
        let inputsFile = document.querySelectorAll('input[type="file"]')
        for (let i = 0; i < inputsFile.length; i++) {
            let input = inputsFile[i]
            input.addEventListener('change', (event) => {
                let fileName = event.target.files[0].name
                let inputParent = input.parentNode
                input.setAttribute('placeholder', fileName)
                if (inputParent) {
                    let label = inputParent.querySelector('.custom-file-label')
                    if (label) {
                        label.innerHTML = fileName
                    }
                }
            })
        }
    }
    fileChange();
    ajaxRowProcess();
}