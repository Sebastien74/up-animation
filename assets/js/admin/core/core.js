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
import '../bootstrap/dist/tab';
import '../bootstrap/dist/popover';
import '../bootstrap/dist/collapse';
import '../bootstrap/dist/modal';
import '../bootstrap/dist/button';

import './perfect-scrollbar.jquery.min';
import './sidebarmenu';
import './sticky-kit';
import './jquery.sparkline.min';
import './custom';
import './tree-list';
import 'simplebar';
import route from "../../vendor/components/routing";

/** 2 - Scroll to errors */
let errors = $(document).find('.invalid-feedback');
if (errors.length > 0) {
    import('../../vendor/components/scroll-error').then(({default: scrollErrors}) => {
        new scrollErrors();
    }).catch(error => console.error(error.message));
}

/** 3 - Ajax GET refresh */
import('./ajax-get').then(({default: ajaxGet}) => {
    new ajaxGet();
}).catch(error => console.error(error.message));

/** 4 - Remove saying href attribute */
$('#saying').find('a').removeAttr('href').addClass('text-info');

/** 5 - To remove cache dir */
const queryString = window.location.search;
const urlParams = new URLSearchParams(queryString);
const cacheClear = urlParams.get('cache_clear');
if (cacheClear) {
    let body = $('body');
    let website = body.data('id');
    $.ajax({
        url: route('cache_clear', {website: website, 'clear': true}),
        type: "GET",
        processData: false,
        contentType: false,
        async: true,
        dataType: 'json',
        complete: function (response) {
            window.location = window.location.pathname;
        }
    });
}

/** 6 - Close command console */
$(document).on('click', '.close-console', function () {
    $("#coresphere_consolebundle_console").fadeOut();
});