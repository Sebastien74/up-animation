const closeMenu = function () {
    let body = document.body;
    body.classList.remove('menu-open');
    document.querySelectorAll('.submenu.show, .dropdown-menu.show').forEach(function (submenu) {
        submenu.classList.remove('collapse');
        submenu.classList.remove('show');
    });
    document.querySelectorAll('.link-level-1.is-active').forEach(function (link) {
        link.classList.remove('is-active');
    });
    document.querySelectorAll('.nav-toggler-icon.open').forEach(function (toggler) {
        toggler.classList.remove('open');
    });
}

const dropdownBack = function (submenu) {
    let backBtn = submenu.querySelector('.dropdown-back');
    if (backBtn) {
        backBtn.onclick = function () {
            let submenu = backBtn.closest('.submenu');
            if (!submenu) {
                submenu = backBtn.closest('.dropdown-menu');
            }
            if (submenu) {
                submenu.classList.remove('show');
                submenu.classList.remove('active');
            }
        }
    }
}

export function collapseEvent(body) {
    document.querySelectorAll('.navbar-collapse').forEach(function (collapse) {
        collapse.addEventListener('show.bs.collapse', function () {
            body.classList.add('menu-open');
            collapse.closest('.navbar').classList.add('open');
            document.querySelectorAll('.nav-toggler-icon').forEach(function (toggle) {
                toggle.classList.add('open');
            });
        })
        collapse.addEventListener('hide.bs.collapse', function () {
            closeMenu();
            body.classList.remove('menu-open');
            collapse.closest('.navbar').classList.remove('open');
            document.querySelectorAll('.nav-toggler-icon').forEach(function (toggle) {
                toggle.classList.remove('open');
            });
        })
    });
}

export function scrollEvent(nav) {

    const scrollAfterSection = false;
    const body = document.body;
    const firstSection = scrollAfterSection ? document.querySelector('.template-page').querySelector('section') : false;
    const firstSectionHeight = firstSection ? firstSection.clientHeight : 0;
    const menuContainer = nav.closest('.menu-container');
    const navHeight = nav.clientHeight;
    const scrollLimitAnimation = nav.clientHeight + 45;
    const scrollLimit = firstSectionHeight > navHeight ? firstSectionHeight + 300 : (scrollLimitAnimation + nav.clientHeight + 45) * 2;
    const getWindowScrollPosition = () => ({
        x: window.scrollX,
        y: window.scrollY
    });
    const position = getWindowScrollPosition();
    if (position.y >= scrollLimit) {
        nav.classList.add('as-scroll');
        body.classList.add('menu-as-scroll');
    }

    let scrollPosition = 0;
    let scrollDirection;
    window.addEventListener('scroll', function () {
        scrollDirection = (document.body.getBoundingClientRect()).top > scrollPosition ? 'up' : 'down';
        scrollPosition = (document.body.getBoundingClientRect()).top;
        if (nav && scrollDirection === 'down' && Math.abs(scrollPosition) >= scrollLimitAnimation && !nav.classList.contains('as-animation') && !nav.classList.contains('as-scroll')) {
            nav.classList.add('as-animation');
            menuContainer.classList.add('as-animation');
            body.classList.add('menu-as-animation');
        } else if (nav && scrollDirection === 'down' && Math.abs(scrollPosition) >= scrollLimit && !nav.classList.contains('as-scroll')) {
            nav.classList.add('as-scroll');
            body.classList.add('menu-as-scroll');
            nav.classList.remove('as-animation');
            menuContainer.classList.remove('as-animation');
            body.classList.remove('menu-as-animation');
        } else if (nav && scrollDirection === 'up' && Math.abs(scrollPosition) < (scrollLimit - 100) && nav.classList.contains('as-scroll')) {
            nav.classList.remove('as-scroll');
            body.classList.remove('menu-as-scroll');
        }
        if (scrollDirection === 'up') {
            nav.classList.remove('as-animation');
            menuContainer.classList.remove('as-animation');
            nav.classList.remove('force-as-scroll');
            body.classList.remove('menu-as-animation');
        }
    });
}

export function lateralMenu(body, nav, screenWidth) {

    let overlay = nav.querySelector('.overlay');
    if (overlay) {
        overlay.addEventListener('click', () => {
            closeMenu();
        });
    }

    nav.querySelectorAll('.close-menu').forEach(function (btn) {
        btn.addEventListener('click', () => {
            closeMenu();
            if (screenWidth <= 991) {
                let burgerBtn = nav.querySelector('.navbar-toggler');
                if (burgerBtn) {
                    burgerBtn.click();
                }
            }
        });
    });

    nav.querySelectorAll('.close-menu-submenu').forEach(function (btn) {
        btn.addEventListener('click', () => {
            let submenu = btn.closest('.submenu');
            submenu.classList.remove('show');
            submenu.classList.remove('active');
            body.classList.remove('menu-open');
        });
    });

    nav.querySelectorAll('.dropdown').forEach(function (dropdown) {
        let link = dropdown.querySelector('.nav-link');
        if (link) {
            let submenu = dropdown.querySelector('.submenu');
            link.addEventListener('click', function (e) {
                e.preventDefault();
                let firstLevelLink = link.classList.contains('link-level-1') ? link : link.closest('.dropdown.level-1').querySelector('.link-level-1');
                nav.querySelectorAll('.link-level-1').forEach(function (firstLink) {
                    if (firstLevelLink && firstLink !== firstLevelLink) {
                        firstLink.classList.remove('is-active');
                        firstLink.classList.remove('active');
                        firstLink.parentNode.querySelectorAll('.submenu').forEach(function (otherSubmenu) {
                            otherSubmenu.classList.remove('show');
                            otherSubmenu.classList.remove('active');
                        });
                    }
                });
                link.classList.toggle('is-active');
                link.classList.toggle('active');
                submenu.classList.toggle('show');
                submenu.classList.toggle('active');
                body.classList.add('menu-open');
                dropdownBack(submenu);
                const submenuActive = document.querySelector('.submenu.active');
                if (!submenuActive) {
                    body.classList.remove('menu-open');
                }
            });
        }
    });
}

export function dropdownHoverAndClick(screenWidth, breakpoint) {

    const dropdownsClick = function (navigation) {

        navigation.querySelectorAll('.dropdown-toggle').forEach(function (dropdown) {
            dropdown.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const submenu = this.nextElementSibling;
                if (submenu) {
                    submenu.classList.toggle('active');
                    submenu.classList.toggle('show');
                    dropdownBack(submenu);
                }
            });
        });

        document.addEventListener('click', function (e) {
            const isClickInsideMenu = navigation.contains(e.target);
            if (!isClickInsideMenu) {
                navigation.querySelectorAll('.dropdown-menu .active').forEach(function (menu) {
                    menu.classList.remove('active');
                });
            }
        });
    }

    let hoverDropdownNavs = document.querySelectorAll('.menu-container.dropdown-hover');
    let clickDropdownNavs = document.querySelectorAll('.menu-container.dropdown-click');

    if (hoverDropdownNavs.length > 0) {
        hoverDropdownNavs.forEach(function (navigation) {
            if (screenWidth >= breakpoint) {
                let dropdowns = navigation.querySelectorAll('.dropdown:not(.switcher)');
                dropdowns.forEach(function (dropdown) {
                    dropdown.addEventListener("mouseenter", function () {
                        dropdown.classList.add('show');
                        let dropdownMenu = dropdown.querySelector('.dropdown-menu');
                        if (dropdownMenu) {
                            dropdownMenu.classList.add('show');
                        }
                    });
                    dropdown.addEventListener("mouseleave", function () {
                        dropdown.classList.remove('show');
                        dropdown.querySelectorAll('.dropdown-menu').forEach(function (dropdownMenu) {
                            dropdownMenu.classList.remove('show');
                        });
                    });
                });
            } else {
                dropdownsClick(navigation);
            }
        });
    } else if (clickDropdownNavs.length > 0) {
        clickDropdownNavs.forEach(function (navigation) {
            dropdownsClick(navigation);
        });
    }
}

export function dropdownManagement(screenWidth, breakpoint) {

    if (screenWidth >= breakpoint) {

        /** Dropdowns management */
        const getNextSibling = function (elem, selector) {
            let sibling = elem.nextElementSibling;
            if (!selector) return sibling
            while (sibling) {
                if (sibling.matches(selector)) return sibling
                sibling = sibling.nextElementSibling;
            }
        }

        let dropdownSubmenus = document.querySelectorAll('.dropdown-submenu > .dropdown-toggle');
        dropdownSubmenus.forEach(function (submenu) {
            submenu.onclick = function (event) {
                event.preventDefault();
                dropdownSubmenus.forEach(function (el) {
                    let next = getNextSibling(el, '.dropdown-menu');
                    if (el.getAttribute('id') !== submenu.getAttribute('id')) {
                        next.classList.remove('show');
                    } else {
                        next.classList.toggle('show');
                    }
                });
                event.stopPropagation();
            }
        });

        document.querySelectorAll('.dropdown').forEach(function (dropdown) {
            dropdown.addEventListener('hidden.bs.dropdown', function () {
                document.querySelectorAll('.dropdown-menu.show').forEach(function (dropdownShow) {
                    /** hide any open menus when parent closes */
                    dropdownShow.classList.remove('show');
                });
            });
        });
    }
}

export function activeClasses(nav) {

    if (nav) {
        nav.querySelectorAll('.link-level-1').forEach(function (link) {
            let href = link.getAttribute('href');
            let windowHref = window.location.href;
            if (windowHref.indexOf(href) !== -1 && !link.classList.contains('active') && href !== '/') {
                link.classList.add('active');
            }
        });
    }

    document.querySelectorAll('.dropdown-menu').forEach(function (dropdownMenu) {
        dropdownMenu.querySelectorAll('li.active').forEach(function (active) {
            let firstLevel = active.closest('.level-1');
            if (firstLevel && !firstLevel.classList.contains('active')) {
                firstLevel.classList.add('active');
                let active = firstLevel.querySelector('a.link-level-1');
                if (active) {
                    active.classList.add('active');
                }
            }
        });
    });

    let breadcrumb = document.getElementById('breadcrumb');
    if (breadcrumb) {
        let firstBreadcrumb = breadcrumb.querySelector("a[data-position='2']");
        if (firstBreadcrumb) {
            let firstBreadcrumbHref = firstBreadcrumb.getAttribute('href');
            let itemMenu = document.querySelector("a.link-level-1[href='" + firstBreadcrumbHref + "']");
            if (itemMenu && !itemMenu.classList.contains('active')) {
                itemMenu.classList.add('active');
                itemMenu.parentNode.classList.add('active');
            }
        }
    }
}

export function anchorsEvents() {

    const body = document.body;

    const events = function () {

        /** Anchors offset of element */
        let mainNavigation = document.getElementById('main-navigation');

        if (mainNavigation) {

            let scrollToAnchor = function (elToScroll) {
                let elOffset = elToScroll.getBoundingClientRect().top + window.scrollY;
                let navbarContainer = mainNavigation.closest('.menu-container');
                let navbarHeight = navbarContainer.classList.contains('sticky-top') || navbarContainer.classList.contains('fixed-top') ? mainNavigation.clientHeight : 0;
                let offset = elOffset - navbarHeight;
                window.scrollTo({top: offset, behavior: 'smooth'});
                if (!body.classList.contains('scroll-fix')) {
                    setTimeout(function () {
                        scrollToAnchor(elToScroll);
                        body.classList.add('scroll-fix');
                    }, 500);
                }
            }

            /** Anchors links on page loaded */
            document.addEventListener('DOMContentLoaded', function () {
                let queryHash = window.location.hash;
                if (queryHash && !queryHash.includes('#/')) {
                    let elToScroll = document.querySelector(queryHash);
                    if (elToScroll) {
                        scrollToAnchor(elToScroll);
                    }
                }
            }, false);

            /** Anchors links on click */
            document.querySelectorAll('.menu-container').forEach(function (navigation) {
                let navbar = navigation.querySelector('nav');
                if (navbar) {
                    navigation.querySelectorAll('.as-anchor').forEach(function (anchor) {
                        let elToScroll = document.querySelector(anchor.dataset.anchor);
                        if (elToScroll) {
                            anchor.onclick = function (event) {
                                event.preventDefault();
                                scrollToAnchor(elToScroll);
                            }
                        }
                    });
                }
            });
        }
    }

    events();
    window.addEventListener('resize', function () {
        events();
    });
}