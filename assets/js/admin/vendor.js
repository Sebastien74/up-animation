/**
 * Vendor
 *
 * @copyright 2020
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @version 1.0
 * @licence under the MIT License (LICENSE.txt)
 *
 *  1 - jQuery UI
 *  2 - Routing
 *  3 - Preloader
 *  4 - Layout management
 *  6 - Core
 *  6 - Active URL
 *  7 - Code generator
 *  8 - Bytes generator
 *  9 - Password generator
 *  10 - Tree search
 *  11 - Index search
 *  12 - Medias modal library
 *  13 - Map
 *  14 - Delete pack
 *  15 - Delete index
 *  16 - Media Tab
 *  17 - Websites selector
 *  18 - Tab item click
 */

import './bootstrap';
import {Tooltip} from './bootstrap-modules';
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

/** 1 - jQuery UI */
import 'jquery-ui/dist/jquery-ui.min';

/** 2 - Routing */
import Routing from '../../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';

/** 4 - Layout management */
if (document.getElementById('zones-sortable')) {
    import('./pages/layout/vendor').then(({default: layoutManagement}) => {
        layoutManagement(Routing);
    }).catch(error => console.error(error.message));
}

/** 5 - Core */
import "../vendor/first-paint";
import "../vendor/vendor";
import "./core/core";
import './form/vendor';
// import './media/cache-resolve';

import pluginsVendor from './plugins/vendor';

pluginsVendor();

/** 6 - Active URL */
    document.body.addEventListener('click', function (e) {
        const link = e.target.closest('.active-urls a');
        if (link) {
            e.preventDefault();
            import('./core/urls').then(({default: activeUrls}) => {
                new activeUrls(e, link);
            }).catch(error => console.error(error.message));
        }
    });

/** 7 - Code generator */
document.body.addEventListener('click', function (e) {
    const link = e.target.closest('.generate-code');
    if (link) {
        e.preventDefault();
        import('./core/code-generator').then(({default: codeGenerator}) => {
            codeGenerator();
        }).catch(error => console.error(error.message));
    }
});

/** 8 - Bytes generator */
document.body.addEventListener('click', function (e) {
    const link = e.target.closest('.generate-bytes');
    if (link) {
        e.preventDefault();
        import('./core/bytes-generator').then(({default: bytesGenerator}) => {
            new bytesGenerator(e, link);
        }).catch(error => console.error(error.message));
    }
});

/** 9 - Password generator */
document.body.addEventListener('click', function (e) {
    const link = e.target.closest('.generator-password');
    if (link) {
        e.preventDefault();
        import('./core/password-generator').then(({default: passwordGenerator}) => {
            new passwordGenerator(e, link);
        }).catch(error => console.error(error.message));
    }
});

/** 10 - Tree search */
const treeSearchInput = document.querySelector('.pages-search input');
if (treeSearchInput) {
    import('./core/tree-search').then(({default: treeSearch}) => {
        treeSearch();
    }).catch(error => console.error(error.message));
}

/** 11 - Index search */
const indexSearchInput = document.querySelector('.search-in-list input');
if (indexSearchInput) {
    import('./core/search').then(({default: search}) => {
        search();
    }).catch(error => console.error(error.message));
}

/** 12 - Medias modal library */
    document.body.addEventListener('click', function (e) {
        const modalEl = e.target.closest('.open-modal-medias');
        if (modalEl) {
            e.preventDefault();
            import('./media/open-modal').then(({default: openModal}) => {
                new openModal(Routing, e, modalEl);
            }).catch(error => console.error(error.message));
        }
    });

/** 13 - Map */
// if (document.querySelectorAll('.input-places').length > 0) {
//     import('./lib/map').then(({default: mapLibrary}) => {
//         new mapLibrary()
//     }).catch(error => console.error(error.message));
// }

    /** 14 - Delete pack */
    if (body.querySelector('.delete-pack') || document.getElementById('delete-pack-btn')) {
        import('./delete/delete-pack').then(({default: deletePack}) => {
            deletePack();
        }).catch(error => console.error(error.message));
    }

    /** 15 - Delete index */
    if (document.getElementById('delete-index-all')
        || document.getElementById('index-delete-show')
        || body.querySelector('.delete-input-index')
        || document.getElementById('index-delete-submit')) {
        import('./delete/delete-index').then(({default: deleteIndex}) => {
            deleteIndex();
        }).catch(error => console.error(error.message));
    }

/** 16 - Media Tab */
document.body.addEventListener('click', function (e) {
    const mediasTabEl = e.target.closest('.media-tab-content-loader');
    if (mediasTabEl) {
        import('./core/medias-tab').then(({default: mediasTab}) => {
            new mediasTab(Routing, mediasTabEl);
        }).catch(error => console.error(error.message));
    }
});

import websitesSelector from './core/websites-selector'

const toastElList = document.querySelectorAll('.toast')
toastElList.forEach(function (el) {
    let close = el.querySelector('.btn-close');
    close.onclick = function () {
        el.classList.remove('show');
    };
    if (!el.classList.contains('bg-danger') && !el.classList.contains('bg-warning') && !el.classList.contains('always-show')) {
        setTimeout(function () {
            el.classList.remove('show');
        }, 5000);
    }
});

window.addEventListener("load", function () {

    /** 17 - Websites selector */
if (document.getElementById('websites-selector-form')) {
    import('./core/websites-selector').then(({default: websitesSelector}) => {
        websitesSelector();
    }).catch(error => console.error(error.message));
}

    /** 18 - Tab item click */
    document.body.addEventListener('click', function (e) {
        const navLinkEl = e.target.closest('.nav-link');
        if (navLinkEl) {
            import('./core/tab').then(({default: tabPlugin}) => {
                new tabPlugin.call(navLinkEl);
            }).catch(error => console.error(error.message));
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    Tooltip();
    document.querySelectorAll('[data-scroll-to]').forEach(function (el) {
        el.onclick = function (e) {
            const targetId = el.getAttribute('data-scroll-to');
            const target = targetId ? document.querySelector(targetId) : false;
            if (target) {
                e.preventDefault();
                scrollToEL(target, false);
            }
        };
    });
});