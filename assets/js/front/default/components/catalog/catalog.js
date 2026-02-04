import Tooltip from '../../../bootstrap/dist/tooltip';
import {hideLoader, displayLoader} from '../loader';
import {AjaxPagination} from '../../functions';

/**
 * Catalog filters / ajax listing
 *
 * @author Sébastien FOURNIER
 */
export default function () {

    const indexProducts = document.getElementById('index-products');
    const formHome = document.querySelector('#content-home #search-filter-form');

    if (!indexProducts) {
        return;
    }

    /**
     * Bind dropdown-select -> hidden/select field value.
     * Delegated so it keeps working after AJAX DOM replacement.
     */
    const bindDropdownSelect = () => {

        if (document.documentElement.dataset.bindDropdownSelect === '1') {
            return;
        }
        document.documentElement.dataset.bindDropdownSelect = '1';

        document.addEventListener('click', (event) => {

            const item = event.target?.closest?.('.dropdown-select .dropdown-item');
            if (!item) {
                return;
            }

            const form = item.closest('form') || document;
            const fieldSelector = item.dataset.fieldId;
            const dropdown = item.closest('.dropdown');
            const clearWrap = dropdown.querySelector('.reset-select-wrap');
            const clear = clearWrap.querySelector('.clear');
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const value = item.dataset.value;

            toggle.innerHTML = item.dataset.label;

            console.log(clearWrap);
            console.log(formHome);

            if (formHome && clearWrap) {
                clearWrap.classList.remove('d-none');
                clear.onclick = () => {
                    toggle.innerHTML = toggle.dataset.placeholder;
                    field.value = '';
                }
            }

            if (!fieldSelector) {
                return;
            }

            const field = form.querySelector(fieldSelector);
            if (!field) {
                return;
            }

            // Update underlying field
            field.value = value ?? '';

            if (!formHome) {
                // Trigger listeners (your filters rely on "change") :contentReference[oaicite:1]{index=1}
                field.dispatchEvent(new Event('input', {bubbles: true}));
                field.dispatchEvent(new Event('change', {bubbles: true}));
            }
        });
    };

    if (formHome) {
        bindDropdownSelect(formHome);
        return;
    }

    hideLoader(indexProducts);
    AjaxPagination(indexProducts);

    /**
     * Bind "Enter" key on search inputs and click on submit icon (delegated).
     */
    const bindSearchEnter = () => {

        if (indexProducts.dataset.bindSearchEnter === '1') {
            return;
        }

        indexProducts.dataset.bindSearchEnter = '1';

        indexProducts.addEventListener('keydown', (event) => {
            const input = event.target?.closest?.('input[type="search"]');
            if (!input) {
                return;
            }
            if (event.key === 'Enter' || event.code === 'Enter') {
                const group = input.closest('.input-group');
                const submitText = group ? group.querySelector('.input-group-text') : null;
                if (submitText) {
                    submitText.click();
                    event.preventDefault();
                }
            }
        });

        indexProducts.addEventListener('click', (event) => {
            const submitText = event.target?.closest?.('.input-group-text');
            if (!submitText) {
                return;
            }

            const form = submitText.closest('form');
            if (form) {
                post(form);
            }
        });
    };

    /**
     * Bind sidebar toggle/reset behaviors and auto-open if at least one filter is active.
     * Uses delegation on document to avoid rebinding after AJAX.
     */
    const bindSidebarEvents = () => {

        if (document.documentElement.dataset.bindSidebarEvents === '1') {
            return;
        }
        document.documentElement.dataset.bindSidebarEvents = '1';

        document.addEventListener('click', (event) => {

            const sidebar = document.querySelector('.filter-sidebar');
            if (!sidebar) {
                return;
            }

            const toggle = event.target?.closest?.('.sidebar-toggle');
            if (toggle) {
                sidebar.classList.toggle('show');
                return;
            }

            const resetBtn = event.target?.closest?.('.reset-sidebar-filters');
            if (resetBtn) {
                // Reset all fields inside sidebar then post
                sidebar.querySelectorAll('select, input').forEach((el) => {
                    el.classList.add('is-refresh');
                    if (el.tagName === 'SELECT') {
                        el.value = '';
                    } else if (el.type === 'checkbox' || el.type === 'radio') {
                        el.checked = false;
                    } else {
                        el.value = '';
                    }
                });

                const form = sidebar.querySelector('form');
                if (form) {
                    post(form, resetBtn);
                }
            }
        });
    };

    /**
     * Auto-open sidebar if any filter is active (run after initial load and after AJAX).
     */
    const autoOpenSidebarIfActive = () => {

        const sidebar = document.querySelector('.filter-sidebar');
        if (!sidebar || sidebar.classList.contains('show')) {
            return;
        }

        sidebar.querySelectorAll('select, input[type="checkbox"], input[type="radio"]').forEach((el) => {
            const hasValue =
                (el.tagName === 'SELECT' && el.value !== '') ||
                (el.type === 'checkbox' && el.checked && el.value) ||
                (el.type === 'radio' && el.checked && el.value);

            if (hasValue) {
                sidebar.classList.add('show');
            }
        });
    };

    /**
     * Bind filter fields (change + clear) using delegation to avoid rebind.
     */
    const bindFilterFields = () => {

        if (document.documentElement.dataset.bindFilterFields === '1') {
            return;
        }
        document.documentElement.dataset.bindFilterFields = '1';

        // Clear buttons
        document.addEventListener('click', (event) => {
            const clearBtn = event.target?.closest?.('.group .clear');
            if (!clearBtn) {
                return;
            }

            const group = clearBtn.closest('.group');
            if (!group) {
                return;
            }

            // Find the associated field in the group
            const selector = group.querySelector('.select-search, .form-check-input');
            if (!selector) {
                return;
            }

            if (selector.tagName === 'SELECT') {
                selector.value = '';
            } else if (selector.type === 'checkbox' || selector.type === 'radio') {
                selector.checked = false;
            } else {
                selector.value = '';
            }

            const form = selector.closest('form');
            if (form) {
                post(form);
            }
        });

        // Generic changes (excluding btn-group-toggle input handled below)
        document.addEventListener(
            'change',
            (event) => {
                const target = event.target;

                const isFilter =
                    target?.matches?.('.select-search, .form-check-input') &&
                    !target.classList.contains('is-refresh');

                if (!isFilter) {
                    return;
                }

                // Avoid double post: inputs inside .btn-group-toggle are handled separately
                if (target.tagName === 'INPUT' && target.closest('.btn-group-toggle')) {
                    return;
                }

                const form = target.closest('form');
                if (form) {
                    post(form, target);
                }
            },
            false
        );

        // btn-group-toggle (toggle active class + post once)
        document.addEventListener(
            'change',
            (event) => {
                const input = event.target?.closest?.('.btn-group-toggle input');
                if (!input) {
                    return;
                }

                const wrapper = input.closest('.btn-group-toggle');
                const label = wrapper ? wrapper.querySelector('label') : null;

                if (label) {
                    label.classList.toggle('active');
                }

                const form = input.closest('form');
                if (form) {
                    post(form, input);
                }

                event.stopImmediatePropagation();
            },
            true
        );
    };

    /**
     * Init tooltips in a given container.
     *
     * @param {HTMLElement|Document} root
     */
    const initTooltips = (root) => {
        if (!root) {
            return;
        }
        root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((tooltipEl) => new Tooltip(tooltipEl));
    };

    /**
     * Submit a form via AJAX (GET) and refresh results + filters UI.
     *
     * @param {HTMLFormElement} form
     * @param {HTMLElement|null} selector
     */
    const post = (form, selector = null) => {
        if (!form) {
            return;
        }

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
        const action = url
            ? `${form.getAttribute('action')}${url}&ajax=true&_locale=${locale}`
            : `${form.getAttribute('action')}?ajax=true&_locale=${locale}`;

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

            // Results
            const container = document.getElementById('results');
            const rspContainer = html.querySelector('#results');
            if (container && rspContainer) {
                container.innerHTML = rspContainer.innerHTML;
            }

            // URL
            window.history.replaceState({}, document.title, pathname + url);

            // Pagination datasets
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

            // Show more button
            const showMoreDoc = document.querySelector('#show-more-wrap');
            if (showMoreDoc && container) {
                (parseInt(container.dataset.max, 10) > 1 ? showMoreDoc.classList.remove : showMoreDoc.classList.add).call(
                    showMoreDoc.classList,
                    'd-none'
                );
            }

            // Tooltips
            if (container) {
                initTooltips(container);
            }

            // Counter
            const resultCounter = document.querySelector('#result-counter');
            const rspCounter = html.querySelector('#result-counter');
            if (resultCounter && rspCounter) {
                resultCounter.classList.remove('d-none');
                resultCounter.innerHTML = rspCounter.innerHTML;
            }

            // Filters container (HTML replaced)
            const formContainer = document.getElementById('search-products-filters-container');
            const rspFormContainer = html.querySelector('#search-products-filters-container');
            if (formContainer && rspFormContainer) {
                formContainer.innerHTML = rspFormContainer.innerHTML;
                // Re-init tooltips inside filters if any
                initTooltips(formContainer);
            }
            // Re-run pagination binding if your helper expects updated DOM
            AjaxPagination(indexProducts);
            // Ensure sidebar state updated
            autoOpenSidebarIfActive();
            hideLoader(indexProducts);
            unlock();
        };

        xHttp.onerror = unlock;
    };

    /**
     * Build a query string from FormData and remove empty params + a given parameter.
     *
     * @param {HTMLFormElement} form
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

    // Initial bindings (all delegated / idempotent)
    bindSearchEnter();
    bindSidebarEvents();
    bindDropdownSelect();
    bindFilterFields();
    autoOpenSidebarIfActive();

    // Initial tooltips
    initTooltips(document);
}