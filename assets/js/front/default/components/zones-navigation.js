import {isElementInMiddleOfScreen, initialPosition} from "../functions";

/**
 * Zone navigation
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (nav) {

    const zone = nav.closest('.layout-zone');

    if (zone) {
        zone.classList.add('layout-zone-navigation');
    }

    function scrollToEL(el) {
        const mainNav = document.querySelector('.menu-container');
        const navbarHeight = (mainNav && mainNav.classList.contains('sticky-top')) || (mainNav && mainNav.classList.contains('fixed-top')) ? mainNav.clientHeight : 0;
        const elOffset = el.getBoundingClientRect().top + window.scrollY;
        const nav = document.querySelector('.zones-navigation');
        let offset = !nav.classList.contains('fixed-top') ? elOffset - navbarHeight - nav.clientHeight : elOffset - navbarHeight;
        window.scrollTo({top: offset, behavior: 'smooth'});
    }

    function activeItems() {
        let items = nav.querySelectorAll('.item');
        items.forEach(item => {
            item.classList.remove('selected', 'text-secondary');
            item.classList.add('text-light');
            let section = document.querySelector('#' + item.dataset.section);
            if (section && isElementInMiddleOfScreen(section) && !item.classList.contains('selected')) {
                if (item.dataset.section === section.getAttribute('id')) {
                    item.classList.add('selected', 'text-secondary');
                    item.classList.remove('text-light');
                }
            }
        });
    }

    let scrollPosition = 0;
    let scrollDirection;
    let startPosition = initialPosition(nav);
    let items = nav.querySelectorAll('.item');
    activeItems(items);

    let hashValue = window.location.hash.substring(1);

    items.forEach(item => {
        item.onclick = function (e) {
            e.preventDefault();
            let asDropdown = item.classList.contains('dropdown-item');
            if (asDropdown) {
                let button = item.closest('.dropdown').querySelector('.dropdown-label');
                button.innerText = item.innerText;
            }
            items.forEach(item => {
                item.classList.remove('selected', 'fw-700');
            });
            item.classList.add('selected', 'fw-700');
            let section = document.querySelector('#' + item.dataset.section);
            scrollToEL(section);
        };
        if (hashValue && item.dataset.section === hashValue && !item.classList.contains('selected')) {
            item.classList.add('selected', 'fw-700');
            let section = document.querySelector('#' + item.dataset.section);
            scrollToEL(section);
        }
    });

    window.addEventListener('scroll', function () {
        let mainNav = document.querySelector('.menu-container');
        let navbarHeight = (mainNav && mainNav.classList.contains('sticky-top')) || (mainNav && mainNav.classList.contains('fixed-top')) ? mainNav.clientHeight : 0;
        scrollDirection = (document.body.getBoundingClientRect()).top > scrollPosition ? 'up' : 'down';
        scrollPosition = (document.body.getBoundingClientRect()).top;
        let rect = nav.getBoundingClientRect();
        if (rect.top <= navbarHeight && 'down' === scrollDirection && !nav.classList.contains('fixed-top')) {
            nav.classList.add('fixed-top');
            nav.style.top = navbarHeight + 'px';
        }
        let scrollTop = parseInt(window.pageYOffset) || parseInt(document.documentElement.scrollTop);
        if (scrollTop <= parseInt(startPosition.top) && 'up' === scrollDirection && nav.classList.contains('fixed-top')) {
            nav.classList.remove('fixed-top');
            nav.style.top = '0px';
        }
        activeItems(items);
    });
}