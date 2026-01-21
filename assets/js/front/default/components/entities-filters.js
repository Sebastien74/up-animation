import '../../../../scss/front/default/components/form/_search-fiters.scss';
import scrollToEl from "../../../vendor/components/scroll-to";
import {AjaxPagination} from "../functions";

export default function (forms) {

    let resetForm = function (form) {
        let loader = form.closest('.search-filters-container').querySelector('.loader');
        if (loader) {
            loader.classList.add('d-none');
        }
        let resetBtn = form.querySelector('.reset-entities-filters');
        if (resetBtn) {
            resetBtn.onclick = function () {
                let inputs = document.getElementById(form.getAttribute('id')).querySelectorAll('select, input');
                for (let i = 0; i < inputs.length; i++) {
                    let el = inputs[i];
                    let formGroup = el.closest('.form-group');
                    el.classList.remove('selected');
                    if (formGroup) {
                        formGroup.classList.remove('selected');
                    }
                    if (el.type && el.type === 'checkbox') {
                        el.checked = false;
                    } else if (el.type && el.type === 'select-multiple') {
                        if (formGroup) {
                            let options = formGroup.querySelectorAll('option');
                            for (let j = 0; j < options.length; j++) {
                                options[j].remove();
                            }
                            let items = formGroup.querySelectorAll('.choices__item');
                            for (let j = 0; j < items.length; j++) {
                                items[j].remove();
                            }
                        }
                    } else {
                        el.value = "";
                    }
                }
                post(form);
            }
        }
    }

    forms.forEach(function (form) {
        resetForm(form);
    });

    let isSelected = function (field) {
        let selected = 'INPUT' === field.tagName ? field.checked : field.value;
        let group = field.closest('.form-group') ? field.closest('.form-group') : field.closest('.form-check');
        let label = group ? group.querySelector('label') : null;
        if (selected) {
            field.classList.add('selected');
            if (group) {
                group.classList.add('selected');
            }
            if (label) {
                label.classList.add('selected');
            }
        } else {
            field.classList.remove('selected');
            if (group) {
                group.classList.remove('selected');
            }
            if (label) {
                label.classList.remove('selected');
            }
        }
    }

    forms.forEach(function (form) {
        form.querySelectorAll('select, input, .form-check-input').forEach(function (field) {
            isSelected(field);
            field.addEventListener('change', () => {
                post(form);
            })
        });
    });

    function post(form) {

        const fullUrl = window.location.href;
        const baseUrl = fullUrl.includes('?') ? fullUrl.split('?')[0] : fullUrl;
        const disabledUrl = form.dataset.disabledUrl;

        let formPost = document.getElementById(form.getAttribute('id'));
        let loader = formPost.closest('.search-filters-container').querySelector('.loader');
        let action = formPost.getAttribute('action');
        let queryString = new URLSearchParams(Array.from(new FormData(formPost))).toString();
        let uri = queryString.replace('&search_terms=', '');
        if (uri && !disabledUrl) {
            history.pushState({}, null, baseUrl + '?' + uri);
        } else if (!disabledUrl) {
            let uri = window.location.toString();
            let cleanUri = uri.substring(0, uri.indexOf("?"));
            cleanUri = cleanUri.replace('&search_terms=', '');
            window.history.replaceState({}, document.title, cleanUri);
        }

        loader.classList.remove('d-none');
        loader.classList.add('d-flex');

        let xHttp = new XMLHttpRequest();
        let url = action.indexOf('?') > -1 ? action + '&' + uri : action + '?' + uri;
        xHttp.open("GET", url + '&ajax=true', true);
        xHttp.send();
        xHttp.onload = function () {
            if (this.readyState === 4 && this.status === 200) {
                let response = JSON.parse(this.response);
                let html = document.createElement('div');
                html.innerHTML = response.html;
                let result = html.querySelector('.listing-entities-container');
                let container = document.querySelector('.listing-entities-container');
                // container.style.height = container.clientHeight + 'px';
                container.innerHTML = result.innerHTML;
                let domSelectsMultiple = document.getElementsByClassName('select-choice');
                let domInputPickers = document.querySelectorAll('input.datepicker');
                import('../../../vendor/plugins/choice').then(({default: choices}) => {
                    new choices(domSelectsMultiple);
                }).catch(error => console.error(error.message));
                import('./form/datepicker').then(({default: datepicker}) => {
                    new datepicker(domInputPickers);
                }).catch(error => console.error(error.message));
                document.querySelectorAll('select, input').forEach(function (field) {
                    isSelected(field);
                    field.addEventListener('change', () => {
                        let formPost = field.closest('form');
                        post(formPost);
                    })
                });
                let sliders = container.querySelectorAll('.splide');
                if (sliders.length > 0) {
                    import('./splide-slider').then(({default: slider}) => {
                        new slider(sliders);
                    }).catch(error => console.error(error.message));
                }
                let forms = document.querySelectorAll('.entities-filters-form');
                let form = document.querySelector('.entities-filters-form');
                if (form) {
                    scrollToEl(form);
                }
                AjaxPagination(html);
                forms.forEach(function (form) {
                    resetForm(form);
                });
                loader.classList.add('d-none');
            }
        }
    }
}