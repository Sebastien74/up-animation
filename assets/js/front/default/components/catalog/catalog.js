import Tooltip from '../../../bootstrap/dist/tooltip';
import {hideLoader, displayLoader} from '../loader';
import {AjaxPagination} from '../../functions';

export default function () {

    const indexProducts = document.getElementById('index-products');

    hideLoader(indexProducts);
    AjaxPagination(indexProducts);

    /**
     * Bind sidebar toggle/reset behaviors and auto-open if at least one filter is active.
     *
     * @param {HTMLFormElement|HTMLElement} form
     */
    const sidebarEvent = (form) => {

        const formEl = form ? document.getElementById(form.getAttribute('id')) : document;
        const sidebar = document.querySelector('.filter-sidebar');

        if (!sidebar) {
            return;
        }

        // Auto-open if any filter is active
        sidebar.querySelectorAll('select, input[type="checkbox"], input[type="radio"]').forEach(el => {
            if (sidebar.classList.contains('show')) {
                return;
            }
            const hasValue =
                (el.tagName === 'SELECT' && el.value !== '') ||
                (el.type === 'checkbox' && el.checked && el.value) ||
                (el.type === 'radio' && el.checked && el.value);

            if (hasValue) {
                sidebar.classList.add('show');
            }
        });

        formEl.querySelectorAll('.sidebar-toggle').forEach(toggle => {
            toggle.onclick = () => sidebar.classList.toggle('show');
        });

        sidebar.querySelectorAll('.reset-sidebar-filters').forEach(resetBtn => {
            resetBtn.onclick = () => {
                sidebar.querySelectorAll('select, input').forEach(el => {
                    el.classList.add('is-refresh');
                    if (el.tagName === 'SELECT') {
                        el.value = '';
                    } else if (el.type === 'checkbox' || el.type === 'radio') {
                        el.checked = false;
                    } else {
                        el.value = '';
                    }
                });
                post(formEl, resetBtn);
            };
        });
    };

    /**
     * Bind filter fields events (change + clear buttons) and prevent double post for .btn-group-toggle.
     *
     * @param {HTMLFormElement|HTMLElement} form
     */
    const fields = (form) => {

        if (!form) {
            return;
        }

        const formEl = document.getElementById(form.getAttribute('id'));
        if (!formEl) {
            return;
        }

        formEl.querySelectorAll('.select-search:not(.is-refresh), .form-check-input:not(.is-refresh)').forEach(selector => {

            // Avoid double post: inputs in .btn-group-toggle are handled below
            if (selector.tagName === 'INPUT' && selector.closest('.btn-group-toggle')) {
                return;
            }

            const group = selector.closest('.group');
            const resetBtn = group ? group.querySelector('.clear') : null;

            if (resetBtn) {
                resetBtn.onclick = () => {
                    if (selector.tagName === 'SELECT') {
                        selector.value = '';
                    } else if (selector.type === 'checkbox' || selector.type === 'radio') {
                        selector.checked = false;
                    } else {
                        selector.value = '';
                    }
                    post(formEl);
                };
            }

            selector.addEventListener('change', () => post(formEl, selector), false);
        });

        formEl.querySelectorAll('.btn-group-toggle').forEach(checkboxGroup => {

            const label = checkboxGroup.querySelector('label');
            const input = checkboxGroup.querySelector('input');
            if (!label || !input) {
                return;
            }

            input.addEventListener('change', (event) => {
                label.classList.toggle('active');
                post(formEl, input);
                event.stopImmediatePropagation();
            });
        });
    };

    /**
     * Bind 'Enter' key to submit search inputs via the associated button.
     */
    const keyDown = () => {

        indexProducts.querySelectorAll('input[type="search"]').forEach(inputText => {

            const group = inputText.closest('.input-group');
            const submitText = group ? group.querySelector('.input-group-text') : null;
            if (!submitText) {
                return;
            }

            inputText.addEventListener('keydown', (event) => {
                if (event.keyCode === 13 || event.which === 13) {
                    submitText.click();
                    event.preventDefault();
                    return false;
                }
            });

            submitText.onclick = () => {
                const f = inputText.closest('form');
                if (f) {
                    post(f);
                }
            };
        });
    };
    keyDown();

    const form = document.getElementById('search-filter-form');
    if (form) {
        fields(form);
        sidebarEvent(form);
    }

    /**
     * Submit a form via AJAX (GET) and refresh results + filters UI.
     *
     * @param {HTMLFormElement|HTMLElement} form
     * @param {HTMLElement|null} selector
     */
    const post = (form, selector = null) => {

        displayLoader(indexProducts, false);

        // Lock to prevent double requests
        if (form.classList.contains('is-post')) {
            return;
        }
        form.classList.add('is-post');

        const loader = indexProducts.querySelector('.loader');
        if (loader && selector && selector.closest('.filter-sidebar')) {
            loader.classList.add('full-screen');
        }

        const locale = document.documentElement.lang || '';
        const url = removeParam(form, 'search_terms');
        const action = (url ? form.getAttribute('action') + url + '&ajax=true&_locale=' + locale : form.getAttribute('action') + '?ajax=true&_locale=' + locale);
        const pathname = window.location.pathname;

        const unlock = () => {
            form.classList.remove('is-post');
            if (loader) {
                loader.classList.remove('full-screen');
            }
        };

        const xHttp = new XMLHttpRequest();
        xHttp.open('GET', action, true);
        xHttp.send();

        xHttp.onload = function () {

            if (!(this.readyState === 4 && this.status === 200)) {
                unlock();
                return;
            }

            let response = this.response;
            response = '{' + response.substring(response.indexOf('{') + 1, response.lastIndexOf('}')) + '}';
            response = JSON.parse(response);

            const html = document.createElement('div');
            html.innerHTML = response.html;

            const container = document.getElementById('results');
            const rspContainer = html.querySelector('#results');
            if (container && rspContainer) {
                container.innerHTML = rspContainer.innerHTML;
            }

            window.history.replaceState({}, document.title, pathname + url);

            const scrollWrapper = html.querySelector('#scroll-wrapper');
            const docWrapper = document.querySelector('#scroll-wrapper');
            if (scrollWrapper) {
                if (docWrapper) {
                    docWrapper.dataset.page = scrollWrapper.dataset.page;
                    docWrapper.dataset.max = scrollWrapper.dataset.max;
                }
                if (container) {
                    container.dataset.page = scrollWrapper.dataset.page;
                    container.dataset.max = scrollWrapper.dataset.max;
                }
            }

            const showMoreDoc = document.querySelector('#show-more-wrap');
            if (showMoreDoc && container) {
                (parseInt(container.dataset.max) > 1 ? showMoreDoc.classList.remove : showMoreDoc.classList.add).call(showMoreDoc.classList, 'd-none');
            }

            if (container) {
                container.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tooltipEl => new Tooltip(tooltipEl));
            }

            const resultCounter = document.querySelector('#result-counter');
            const rspCounter = html.querySelector('#result-counter');
            if (resultCounter && rspCounter) {
                resultCounter.classList.remove('d-none');
                resultCounter.innerHTML = rspCounter.innerHTML;
            }

            const formContainer = document.getElementById('search-products-filters-container');
            const rspFormContainer = html.querySelector('#search-products-filters-container');
            if (formContainer && rspFormContainer) {
                formContainer.innerHTML = rspFormContainer.innerHTML;
                fields(formContainer.querySelector('#search-filter-form'));

                const searchTextForm = formContainer.querySelector('#search-text-form');
                if (searchTextForm) {
                    const submitText = searchTextForm.querySelector('.input-group-text');
                    if (submitText) {
                        submitText.onclick = () => post(searchTextForm);
                    }
                }
            }

            AjaxPagination(html);
            keyDown();
            sidebarEvent(form);
            hideLoader(indexProducts);
            unlock();
        };

        xHttp.onerror = unlock;
    };

    /**
     * Build a query string from FormData and remove empty params + a given parameter.
     *
     * @param {HTMLFormElement|HTMLElement} form
     * @param {string} parameter
     * @returns {string}
     */
    const removeParam = (form, parameter) => {
        let sourceURL = '?' + decodeURI(new URLSearchParams(Array.from(new FormData(form))).toString());
        const urlParts = sourceURL.split('?');
        if (urlParts.length >= 2) {
            const urlBase = urlParts.shift();
            const queryString = urlParts.join('?');
            const prefix = encodeURIComponent(parameter) + '=';
            const parameters = queryString.split(/[&;]/g);
            for (let i = parameters.length; i-- > 0;) {
                const values = parameters[i].split('=');
                if (!values[values.length - 1] || parameters[i].lastIndexOf(prefix, 0) !== -1) {
                    parameters.splice(i, 1);
                }
            }
            sourceURL = urlBase + '?' + parameters.join('&');
        }
        return sourceURL === '?' ? '' : sourceURL;
    };
}