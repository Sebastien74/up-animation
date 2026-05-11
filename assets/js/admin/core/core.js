/**
 * Admin Core
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 *  1 - Core
 *  2 - Scroll to errors
 *  3 - Ajax GET refresh
 *  4 - Remove saying href attribute
 *  5 - Close command console
 */

/** 1 - Core */

import '../bootstrap/dist/dropdown';

import './sidebar';
import './tree-list';
import route from "../../vendor/components/routing";

/** 2 - Scroll to errors */
let errors = document.querySelectorAll('.invalid-feedback');
if (errors.length > 0) {
    import('../../vendor/components/scroll-error').then(({default: scrollErrors}) => {
        new scrollErrors();
    }).catch(error => console.error(error.message));
}

/** 3 - Ajax GET refresh */
if (document.querySelectorAll('.modal-btn-position-ajax, .ajax-get-refresh').length > 0) {
    import('./ajax-get').then(({default: ajaxGet}) => {
        new ajaxGet();
    }).catch(error => console.error(error.message));
}

/** 5 - To remove cache dir */
const queryString = window.location.search;
const urlParams = new URLSearchParams(queryString);
const cacheClear = urlParams.get('cache_clear');
if (cacheClear) {
    let website = document.body.dataset.id;
    fetch(route('cache_clear', {website: website, 'clear': true}), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(() => {
            window.location = window.location.pathname;
        });
}

/** 6 - Close command console */
document.addEventListener('click', function (e) {
    if (e.target.closest('.close-console')) {
        const consoleEl = document.getElementById("coresphere_consolebundle_console");
        if (consoleEl) {
            consoleEl.style.transition = 'opacity 0.5s ease-out';
            consoleEl.style.opacity = '0';
            setTimeout(() => {
                consoleEl.style.display = 'none';
            }, 500);
        }
    }
});