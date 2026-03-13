/**
 * Functions
 *
 * @copyright 2026
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 * @licence under the MIT License (LICENSE.txt)
 */

export function scrollToEL(el, middle = true, offset = 0) {
    const mainMenu = document.querySelector('.page-titles');
    const rect = el.getBoundingClientRect();
    const offsetTop = mainMenu ? mainMenu.getBoundingClientRect().height * 1.5 : 0;
    const elOffset = rect.top + window.scrollY;
    const elHeight = rect.height;
    const windowHeight = window.innerHeight;
    if (elHeight < windowHeight && middle) {
        offset = elOffset - ((windowHeight / 2) - (elHeight / 2));
    } else {
        offset = elOffset;
    }
    offset = offsetTop > 0 ? offset - offsetTop : elOffset;
    window.scrollTo({top: offset, behavior: 'smooth'});
}

export function AlertHTML(message, type = 'danger', icon = 'exclamation-triangle', iconColor = 'white-50') {
    let html = '<div class="internal-error-alert alert alert-' + type + ' position-relative d-flex mb-3 p-3">';
    html += '<div class="btn-icon d-flex align-items-center justify-content-center position-relative ' + type + ' me-3">';
    html += '<i class="icm-' + icon + ' text-' + iconColor + '"></i>';
    html += '</div>';
    html += '<div class="message w-100 d-flex align-items-center text-' + iconColor + '">';
    html += '<div class="content">';
    html += message;
    html += '</div>';
    html += '</div>';
    html += '</div>';
    return html;
}