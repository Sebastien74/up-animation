import {activeClasses, scrollEvent} from "./navigation-functions";

/**
 * Navigation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let mainNavigation = document.getElementById('main-navigation');
    let menu = document.getElementById('menu-container-main');
    let screenWidth = window.screen.width;
    let body = document.body;
    let breakpoints = {
        sm: 576,
        md: 768,
        lg: 992,
        xl: 1200,
        xxl: 1600,
        navbar_expand: menu ? menu.dataset.expand : 'lg'
    }
    let breakpoint = menu ? breakpoints[menu.dataset.expand] : breakpoints['lg'];

    /** Collapse events */
    import('./navigation-functions').then(({collapseEvent: CollapseEvent}) => {
        new CollapseEvent(body);
    }).catch(error => console.error(error.message));

    /** Dropdown hover & click */
    let dropdownHover = document.querySelector('.menu-container.dropdown-hover');
    let dropdownClick = document.querySelector('.menu-container.dropdown-click');
    if (dropdownHover || dropdownClick) {
        import('./navigation-functions').then(({dropdownHoverAndClick: DropdownHoverAndClick}) => {
            new DropdownHoverAndClick(screenWidth, breakpoint);
        }).catch(error => console.error(error.message));
    }

    /** Lateral menu */
    let lateralMenu = document.querySelector('.menu-container.as-lateral');
    if (lateralMenu) {
        import('./navigation-functions').then(({lateralMenu: LateralMenu}) => {
            new LateralMenu(body, lateralMenu, screenWidth);
        }).catch(error => console.error(error.message));
    } else {
        import('./navigation-functions').then(({dropdownManagement: DropdownManagement}) => {
            new DropdownManagement(screenWidth, breakpoint);
        }).catch(error => console.error(error.message));
    }

    /** Anchors events */
    let queryHash = window.location.hash;
    let haveAnchors = (queryHash && !queryHash.includes('#/')) || (document.querySelectorAll('.as-anchor').length > 0);
    if (haveAnchors) {
        import('./navigation-functions').then(({anchorsEvents: AnchorsEvents}) => {
            new AnchorsEvents();
        }).catch(error => console.error(error.message));
    }

    activeClasses(mainNavigation);
    scrollEvent(mainNavigation);
}