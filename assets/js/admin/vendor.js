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
import Cookies from "js-cookie";

let body = document.body;

/** To open creation modal after saveAdd submit redirection */
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
//                 if ($(node).is(".ui-helper-hidden-accessible")) {
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
import layoutManagement from './pages/layout/vendor';

if (document.getElementById('zones-sortable')) {
    layoutManagement(Routing);
}

/** 5 - Core */
import "../vendor/first-paint";
import "../vendor/vendor";
import "./core/core";
import './form/vendor';
import './media/cache-resolve';

import pluginsVendor from './plugins/vendor';

pluginsVendor();

/** 6 - Active URL */
let urlLinks = document.querySelectorAll('.active-urls a')
for (let i = 0; i < urlLinks.length; i++) {
    let link = urlLinks[i]
    link.onclick = function (e) {
        e.preventDefault()
        import('./core/urls').then(({default: activeUrls}) => {
            new activeUrls(e, link)
        }).catch(error => console.error(error.message));
    }
}

/** 7 - Code generator */
let generateLinks = document.querySelectorAll('.generate-code')
if (generateLinks.length > 0) {
    import('./core/code-generator').then(({default: codeGenerator}) => {
        new codeGenerator()
    }).catch(error => console.error(error.message));
}

/** 8 - Bytes generator */
let bytesLinks = document.querySelectorAll('.generate-bytes')
for (let i = 0; i < bytesLinks.length; i++) {
    let link = bytesLinks[i]
    link.onclick = function (e) {
        e.preventDefault()
        import('./core/bytes-generator').then(({default: bytesGenerator}) => {
            new bytesGenerator(e, link)
        }).catch(error => console.error(error.message));
    }
}

/** 9 - Password generator */
let passwordLinks = document.querySelectorAll('.generator-password')
for (let i = 0; i < passwordLinks.length; i++) {
    let link = passwordLinks[i]
    link.onclick = function (e) {
        e.preventDefault()
        import('./core/password-generator').then(({default: passwordGenerator}) => {
            new passwordGenerator(e, link)
        }).catch(error => console.error(error.message));
    }
}

/** 10 - Tree search */
if (document.querySelectorAll('.pages-search input').length > 0) {
    import('./core/tree-search').then(({default: treeSearch}) => {
        new treeSearch()
    }).catch(error => console.error(error.message));
}

/** 11 - Index search */
if (document.querySelectorAll('.search-in-list input').length > 0) {
    import('./core/search').then(({default: search}) => {
        new search()
    }).catch(error => console.error(error.message));
}

/** 12 - Medias modal library */
let mediasModals = document.querySelectorAll('.open-modal-medias')
for (let i = 0; i < mediasModals.length; i++) {
    let modalEl = mediasModals[i]
    modalEl.onclick = function (e) {
        e.preventDefault()
        import('./media/open-modal').then(({default: openModal}) => {
            new openModal(Routing, e, modalEl)
        }).catch(error => console.error(error.message));
    }
}

/** 13 - Map */
// if (document.querySelectorAll('.input-places').length > 0) {
//     import('./lib/map').then(({default: mapLibrary}) => {
//         new mapLibrary()
//     }).catch(error => console.error(error.message));
// }

/** 14 - Delete pack */
if (body.getElementsByClassName('delete-pack').length > 0
    || document.getElementById('delete-pack-btn')) {
    import('./delete/delete-pack').then(({default: deletePack}) => {
        new deletePack()
    }).catch(error => console.error(error.message));
}

/** 15 - Delete index */
if (document.getElementById('delete-index-all')
    || document.getElementById('index-delete-show')
    || body.getElementsByClassName('delete-input-index').length > 0
    || document.getElementById('index-delete-submit')) {
    import('./delete/delete-index').then(({default: deleteIndex}) => {
        new deleteIndex()
    }).catch(error => console.error(error.message));
}

/** 16 - Media Tab */
let mediasTabs = document.querySelectorAll('.media-tab-content-loader')
for (let i = 0; i < mediasTabs.length; i++) {
    let mediasTabEl = mediasTabs[i]
    mediasTabEl.onclick = function () {
        import('./core/medias-tab').then(({default: mediasTab}) => {
            new mediasTab(Routing, mediasTabEl)
        }).catch(error => console.error(error.message));
    }
}

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
        websitesSelector()
    }

    /** 18 - Tab item click */
    let navLinks = document.querySelectorAll('.nav-link')
    for (let i = 0; i < navLinks.length; i++) {
        let navLinkEl = navLinks[i]
        navLinkEl.onclick = function () {
            import('./core/tab').then(({default: tabPlugin}) => {
                new tabPlugin()
            }).catch(error => console.error(error.message));
        }
    }
});