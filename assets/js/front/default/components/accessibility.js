/** Import CSS */

import {scrollToEL} from "../functions"

/**
 * Accessibility
 *
 * @copyright 2025
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 * @licence under the MIT License (LICENSE.txt)
 */

export default function () {

    const body = document.body;

    if (body.classList.contains('as-accessibility')) {
        setTimeout(() => {
            import('../../../../scss/front/default/components/accessibility.scss');
        }, 0.1);
    }

    document.querySelectorAll('video').forEach(video => {
        video.setAttribute('tabindex', '-1');
        video.setAttribute('aria-hidden', 'true');
        video.blur();
    });

    // For the focus on Tab event
    document.addEventListener('keydown', function (event) {

        if (event.key === 'Tab') {

            // If focus is on <body>, redirect it to the first focusable element (skip-link fix)
            if (document.activeElement === body && !body.classList.contains('active-for-tab')) {
                const firstFocusable = document.querySelector(
                    'a.skip-link, a[href], button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
                );
                if (firstFocusable) {
                    event.preventDefault();
                    firstFocusable.focus();
                    body.classList.add('active-for-tab')
                    // Renvoie ver le premier skip link
                    return;
                }
            }

            // Highlight currently focused element
            setTimeout(() => {

                const focusedLink = document.activeElement;

                const tags = ['a', 'button', 'input', 'select', 'textarea', 'img'];
                document.querySelectorAll('.focused-el').forEach((link) => {
                    link.classList.remove('focused-el');
                });

                const tagName = focusedLink.tagName.toLowerCase();
                if (tags.includes(tagName)) {
                    focusedLink.classList.add('focused-el');
                    const parentHideWrap = focusedLink.closest('.img-hover-buttons-wrap');
                    if (parentHideWrap) {
                        parentHideWrap.classList.add('focused-el');
                    }
                    console.log(document.querySelector('.focused-el'));
                    scrollToEL(document.querySelector('.focused-el'));
                }

                // To close submenu
                const inSubmenu = focusedLink.closest('.submenu-level-3');
                if (!inSubmenu) {
                    document.querySelectorAll('.submenu-level-3.active').forEach((submenu) => {
                        submenu.classList.remove('active', 'show');
                    });
                }

                // To close menu
                const burgerBtn = document.querySelector('.navbar-toggler');
                const asItemMenu = focusedLink.closest('.navbar-collapse') || focusedLink.classList.contains('navbar-toggler') || focusedLink.classList.contains('navbar-brand');
                if (!asItemMenu && burgerBtn && !burgerBtn.classList.contains('collapsed')) {
                    burgerBtn.click();
                }

                const inSplide = focusedLink.closest('.splide');

                // // Auto-click behavior for collapsed burger menu and accordions
                // if (focusedLink.classList.contains('navbar-toggler') && focusedLink.classList.contains('collapsed')) {
                //     focusedLink.click();
                // } else if (focusedLink.classList.contains('as-dropdown-toggle')) {
                //     focusedLink.click();
                // } else if (focusedLink.classList.contains('accordion-button')) {
                //     focusedLink.click();
                // } else if (focusedLink.classList.contains('leaflet-marker-icon')) {
                //     focusedLink.click();
                // } else if (focusedLink.getAttribute('role') === 'tab' && focusedLink.hasAttribute('aria-controls')) {
                //     focusedLink.click();
                // } else if (focusedLink.classList.contains('map-box')) {
                //     focusedLink.classList.add('focused-el');
                // } else if (inSplide) {
                //     inSplide.classList.add('focused-el');
                // }
            }, 0);
        }
    });
}