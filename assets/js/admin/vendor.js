/**
 * Vendor (admin entry)
 *
 * @copyright 2026
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @licence under the MIT License (LICENSE.txt)
 *
 * Principe : tout est lazy-loadé conditionnellement (présence DOM)
 * pour ne charger que le strict nécessaire sur chaque page.
 */

import './bootstrap';
import {Tooltip, Popover, Collapse, Tab, Modal} from './bootstrap-modules';
import {scrollToEL} from './functions';

import Cookies from "js-cookie";

let body = document.body;

/** To open the creation modal after saveAdd submit redirection */
const queryString = window.location.search;
const urlParams = new URLSearchParams(queryString);
const openModal = urlParams.get('open_modal');
const modalBtn = document.querySelector('.add-open-modal');
if (openModal && modalBtn) {
    modalBtn.click();
}

// const observer = new MutationObserver((mutations) => {
//     mutations.forEach((mutation) => {
//         if (mutation.addedNodes) {
//             mutation.addedNodes.forEach((node) => {
//                 console.log(node)
//                 if (node.classList.contains("ui-helper-hidden-accessible")) {
//                     console.log("Div d'accessibilité ajoutée par :", node);
//                 }
//             });
//         }
//     });
// });
// observer.observe(document.body, { childList: true, subtree: true });

/**
 * Cookies create
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
let setCookie = function (name, value) {
    let secure = location.protocol !== "http:"
    let domainName = window.location.hostname
    let domain = domainName.replace('www.', '')
    Cookies.set(name, value, {expires: 365, path: '/', domain: domain, secure: secure})
}

if (!Cookies.get('SECURITY_IS_ADMIN')) {
    setCookie('SECURITY_IS_ADMIN', true);
}

/** 2 - Routing */
import Routing from '../../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';

/** 5 - Core */
import "../vendor/first-paint";
import "../vendor/vendor";
import "./core/core";

/** 1 - jQuery UI (lazy, seulement si un plugin qui en dépend est présent) */
const jqueryUiSelectors = '#zones-sortable, .nestable-list-container, #medias-sortable-container, .prototype-sortable, .ui-sortable, .datepicker, .colorpicker, .data-table, .tree-select';
const needsJqueryUi = document.querySelector(jqueryUiSelectors);
const jqueryUiReady = needsJqueryUi
    ? import('jquery-ui/dist/jquery-ui.min')
    : Promise.resolve();

/** 4 - Layout management */
if (document.getElementById('zones-sortable')) {
    jqueryUiReady.then(() => import('./pages/layout/vendor'))
        .then(({default: LayoutManagement}) => LayoutManagement(Routing))
        .catch(error => console.error(error.message));
}

jqueryUiReady
    .then(() => Promise.all([
        import('./form/vendor'),
        import('./plugins/vendor').then(({default: pluginsVendor}) => pluginsVendor()),
    ]))
    .catch(error => console.error(error.message));

/** 6-9, 12, 16, 18 - Délégation click unique */
const clickHandlers = [
    {selector: '.active-urls a', preventDefault: true, load: () => import('./core/urls'), call: (e, el, mod) => new mod.default(e, el)},
    {selector: '.generate-code', preventDefault: true, load: () => import('./core/code-generator'), call: (e, el, mod) => mod.default()},
    {selector: '.generate-bytes', preventDefault: true, load: () => import('./core/bytes-generator'), call: (e, el, mod) => new mod.default(e, el)},
    {selector: '.generator-password', preventDefault: true, load: () => import('./core/password-generator'), call: (e, el, mod) => new mod.default(e, el)},
    {selector: '.open-modal-medias', preventDefault: true, load: () => import('./media/open-modal'), call: (e, el, mod) => new mod.default(Routing, e, el)},
    {selector: '.media-tab-content-loader', preventDefault: false, load: () => import('./core/medias-tab'), call: (e, el, mod) => new mod.default(Routing, el)},
    {selector: '.nav-link', preventDefault: false, load: () => import('./core/tab'), call: (e, el, mod) => new mod.default.call(el)},
];

body.addEventListener('click', function (e) {
    for (const handler of clickHandlers) {
        const el = e.target.closest(handler.selector);
        if (el) {
            if (handler.preventDefault) e.preventDefault();
            handler.load().then(mod => handler.call(e, el, mod)).catch(error => console.error(error.message));
            break;
        }
    }
});

/** 10 - Tree search */
if (document.querySelector('.pages-search input')) {
    import('./core/tree-search').then(({default: TreeSearch}) => TreeSearch())
        .catch(error => console.error(error.message));
}

/** 11 - Index search */
if (document.querySelector('.search-in-list input')) {
    import('./core/search').then(({default: Search}) => Search())
        .catch(error => console.error(error.message));
}

/** 14 - Delete pack */
if (body.querySelector('.delete-pack') || document.getElementById('delete-pack-btn')) {
    import('./delete/delete-pack').then(({default: DeletePack}) => DeletePack())
        .catch(error => console.error(error.message));
}

/** 15 - Delete index */
if (document.getElementById('delete-index-all')
    || document.querySelector('.index-delete-show')
    || body.querySelector('.delete-input-index')
    || document.querySelector('.index-delete-submit')
) {
    import('./delete/delete-index').then(({default: DeleteIndex}) => DeleteIndex())
        .catch(error => console.error(error.message));
}

/** Toasts auto-hide */
body.querySelectorAll('.toast').forEach(function (el) {
    const close = el.querySelector('.btn-close');
    if (close) close.onclick = () => el.classList.remove('show');
    if (!el.classList.contains('bg-danger') && !el.classList.contains('bg-warning') && !el.classList.contains('always-show')) {
        setTimeout(() => el.classList.remove('show'), 7500);
    }
});

/** 17 - Websites selector (différé après load) */
window.addEventListener('load', function () {
    if (document.getElementById('websites-selector-form')) {
        import('./core/websites-selector').then(({default: WebsitesSelector}) => WebsitesSelector())
            .catch(error => console.error(error.message));
    }
});

/** Scroll-to (léger, immédiat sans listener global) */
function bindScrollTo() {
    body.querySelectorAll('[data-scroll-to]').forEach(function (el) {
        el.onclick = function (e) {
            const targetId = el.getAttribute('data-scroll-to');
            const target = targetId ? document.querySelector(targetId) : null;
            if (target) {
                e.preventDefault();
                scrollToEL(target, false);
            }
        };
    });
}

/** Init Bootstrap modules en idle (non bloquant pour le first paint) */
function initBootstrapModules() {
    Tooltip();
    Popover();
    Collapse();
    Tab();
    Modal();
    bindScrollTo();
}

const scheduleIdle = window.requestIdleCallback || function (cb) { return setTimeout(cb, 1); };

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => scheduleIdle(initBootstrapModules));
} else {
    scheduleIdle(initBootstrapModules);
}