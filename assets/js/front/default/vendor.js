/**
 * On loaded
 *
 * @copyright 2024
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 * @licence under the MIT License (LICENSE.txt)
 */
import {lazyLoadComponent, RemoveAttrsTitle, scrollToEL} from "./functions";

const html = document.documentElement;
const isDebug = html.dataset.debug ? parseInt(html.dataset.debug) === 1 : false;

/**
 * Bootstrap
 *
 * @copyright 2024
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 * @licence under the MIT License (LICENSE.txt)
 */

function adjustColumnsByMargin() {
    document.querySelectorAll(".layout-block, .layout-col").forEach(col => {
        let style = window.getComputedStyle(col);
        // 1️⃣ Remove previously added max-width to reset to the original CSS
        col.style.maxWidth = "";
        // 2️⃣ Get the width defined in % from the original CSS
        let widthValue = col.style.width || style.getPropertyValue("width");
        let widthPercent = widthValue.includes("%")
            ? parseFloat(widthValue)
            : (parseFloat(style.width) / col.parentElement.clientWidth) * 100;
        // 3️⃣ Check if there is a margin
        let marginRight = parseFloat(style.marginRight) || 0;
        let marginLeft = parseFloat(style.marginLeft) || 0;
        // 4️⃣ Apply calculation ONLY if at least one margin is greater than 0
        if (marginRight > 0 || marginLeft > 0) {
            let parentWidth = col.parentElement.clientWidth || 1; // Get parent width in px
            // 5️⃣ Convert margins from px to % of the parent width
            let totalMarginPercent = ((marginRight + marginLeft) / parentWidth) * 100;
            // 6️⃣ Calculate the new adjusted width (always ≤ original width)
            let newWidthPercent = Math.max(0, widthPercent - totalMarginPercent);
            // 7️⃣ Apply the new max-width
            col.style.maxWidth = `${newWidthPercent}%`;
        }
    });
}

// Prevent unnecessary recalculations on Y-axis resize
let lastWindowWidth = window.innerWidth;

function handleResize() {
    let currentWindowWidth = window.innerWidth;
    if (currentWindowWidth !== lastWindowWidth) {
        adjustColumnsByMargin();
        lastWindowWidth = currentWindowWidth;
    }
}

// Run at load and on X-axis resize only
window.addEventListener("load", adjustColumnsByMargin);
window.addEventListener("resize", handleResize);

document.addEventListener('DOMContentLoaded', function () {

    const body = document.body;

    lazyLoadComponent('#main-preloader', () => import(/* webpackPreload: true */'./components/preloader'), (Preloader) => new Preloader());
    lazyLoadComponent('.media-block', () => import(/* webpackPreload: true */'./components/medias'), (Medias, els) => new Medias(els));
    lazyLoadComponent('.splide:not(.thumbnails-slider)', () => import('./components/splide-slider'), (Sliders, els) => new Sliders(els));
    lazyLoadComponent('.marquee', () => import(/* webpackPreload: true */'./components/marquee'), (Marquees, els) => new Marquees(els));
    lazyLoadComponent('.entities-filters-form', () => import(/* webpackPreload: true */'./components/entities-filters'), (Filters, els) => new Filters(els));
    lazyLoadComponent('.zones-navigation', () => import(/* webpackPreload: true */'./components/zones-navigation'), (Navigations, els) => new Navigations(els));
    lazyLoadComponent('.glightbox', () => import(/* webpackPreload: true */'../../vendor/plugins/popup'), (Popups) => new Popups());
    lazyLoadComponent('[data-component="masonry"]', () => import(/* webpackPreload: true */'./components/masonry'), (Masonry, els) => new Masonry(els));
    lazyLoadComponent('.social-wall-wrap', () => import(/* webpackPreload: true */'./components/social-wall'), (socialWalls, els) => new socialWalls(els));
    lazyLoadComponent('[data-component="counter"]', () => import(/* webpackPreload: true */'./components/counters'), (Counters, els) => new Counters(els));
    lazyLoadComponent('.parallax', () => import(/* webpackPreload: true */'./components/parallax'), (Parallax, els) => new Parallax(els));
    lazyLoadComponent('.share-content', () => import(/* webpackPreload: true */'./components/share'), (ShareBoxes) => new ShareBoxes());
    lazyLoadComponent('#website-alert', () => import(/* webpackPreload: true */'./components/website-alert'), (Alerts) => new Alerts());
    lazyLoadComponent('font', () => import(/* webpackPreload: true */'./components/fonts'), (Fonts) => new Fonts());
    lazyLoadComponent('#webmaster-box', () => import(/* webpackPreload: true */'../../vendor/components/webmaster'), (Webmaster, el) => new Webmaster(el));
    lazyLoadComponent('#scroll-top-btn', () => import(/* webpackPreload: true */'./components/scroll'), (Scroll) => new Scroll());
    lazyLoadComponent('.scroll-link', () => import(/* webpackPreload: true */'./components/scroll'), (Scroll) => new Scroll());
    lazyLoadComponent('.newsletter-form-container', () => import(/* webpackPreload: true */'./components/form/newsletter'), (Newsletters) => new Newsletters());
    lazyLoadComponent('.step-form-ajax', () => import(/* webpackPreload: true */'./components/form/steps-form'), (StepForm) => new StepForm());

    const tab = document.querySelector('.nav-tabs');
    const pill = document.querySelector('.nav-pills');
    if (tab || pill) {
        import('../bootstrap/dist/tab').then(({ default: Tab }) => {
            document.querySelectorAll('.nav-tabs, .nav-pills').forEach(tabToggleEl => {
                tabToggleEl.querySelectorAll('button').forEach(triggerEl => {
                    const tabTrigger = new Tab(triggerEl);
                    triggerEl.addEventListener('click', event => {
                        event.preventDefault();
                        tabTrigger.show();
                    });
                });
            });
        }).catch(error => console.error(error.message));
    }

    const dropdown = document.querySelector('.dropdown-toggle');
    if (dropdown) {
        import('../bootstrap/dist/dropdown').then(({default: Dropdown}) => {
            const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                if (!dropdownToggleEl.classList.contains('loaded')) {
                    dropdownToggleEl.classList.add('loaded')
                    return new Dropdown(dropdownToggleEl);
                }
            });
        }).catch(error => console.error(error.message));
    }

    const collapse = document.querySelector('.collapse');
    if (collapse) {
        import('../bootstrap/dist/collapse').then(({default: Collapse}) => {
            document.querySelectorAll('.collapse').forEach(function (collapseToggleEl) {
                if (!collapseToggleEl.classList.contains('loaded')) {
                    collapseToggleEl.classList.add('loaded')
                    const bsCollapse = new Collapse(collapseToggleEl, {
                        toggle: false
                    });
                }
                // collapseToggleEl.addEventListener('show.bs.collapse', event => {
                //     let parent = event.target.parentNode;
                //     parent.querySelectorAll('.hide-on-collapse').forEach(function (hideEl) {
                //         hideEl.classList.add('d-none');
                //     });
                // });
                // collapseToggleEl.addEventListener('hide.bs.collapse', event => {
                //     let parent = event.target.parentNode;
                //     parent.querySelectorAll('.hide-on-collapse').forEach(function (hideEl) {
                //         hideEl.classList.remove('d-none');
                //     });
                // });
            });
        }).catch(error => console.error(error.message));
    }

    const navigation = document.querySelector('.menu-container');
    if (navigation) {
        import('../bootstrap/modules/navigation').then(({default: Nav}) => {
            new Nav();
        }).catch(error => console.error(error.message));
    }

    const carousel = document.querySelector('.carousel');
    if (carousel) {
        import('../bootstrap/modules/carousel').then(({default: Carousel}) => {
            new Carousel();
        }).catch(error => console.error(error.message));
    }

    const modal = document.querySelector('.modal');
    if (modal) {
        import('../bootstrap/modules/modal').then(({default: Modal}) => {
            new Modal();
        }).catch(error => console.error(error.message));
    }

    const toast = document.querySelector('.toast');
    if (toast) {
        import('../bootstrap/modules/toast').then(({default: Toast}) => {
            new Toast();
        }).catch(error => console.error(error.message));
    }

    const tooltip = document.querySelector('[data-bs-toggle="tooltip"]');
    if (tooltip) {
        import('../bootstrap/modules/tooltip').then(({default: Tooltip}) => {
            new Tooltip();
        }).catch(error => console.error(error.message));
    }

    /** Scroll to el on click */
    document.querySelectorAll(".as-scroll-link").forEach(el => {
        el.onclick = function (e) {
            e.preventDefault();
            const scrollToEl = document.querySelector(el.getAttribute('href'));
            if (scrollToEl) {
                scrollToEL(scrollToEl, false);
            }
        }
    });

    RemoveAttrsTitle();

    /** To remove empty associated entities teaser */
    document.querySelectorAll('.empty-associated-entities').forEach(function (el) {
        const zone = el.closest('.layout-zone');
        if (zone) {
            zone.remove();
        }
    });

    // Target all elements inside .body that have a style attribute
    document.querySelectorAll('.body [style]').forEach(el => {
        // Extract the inline style as individual declarations
        const declarations = el.getAttribute('style').split(';').filter(d => d.trim() !== '');
        // Reconstruct the style with !important
        const newStyle = declarations.map(decl => {
            const [prop, value] = decl.split(':');
            return `${prop.trim()}: ${value.trim()} !important`;
        }).join('; ');
        // Replace the style attribute with the modified version
        el.setAttribute('style', newStyle);
    });

    document.querySelectorAll('link.preload-css[rel="preload"]').forEach(link => {
        link.rel = 'stylesheet';
    });

    document.querySelectorAll('.js-open-window').forEach(button => {
        button.addEventListener('click', () => {
            const url = button.getAttribute('data-url');
            if (url) {
                window.open(url, '_blank', 'noopener,noreferrer');
            }
        });
    });

    const zoomLevel = function () {
        let browserZoomLevel = Math.round(window.devicePixelRatio * 100);
        body.setAttribute('data-browser-zoom-level', browserZoomLevel.toString());
        body.classList.add('zoom-' + browserZoomLevel);
    }
    zoomLevel();

    window.addEventListener('resize', function () {
        zoomLevel();
    });

    import('../../vendor/components/lazy-load').then(({default: lazyLoad}) => {
        new lazyLoad();
    }).catch(error => console.error(error.message));

    /** To set overflow to sticky parents elements */
    function getParentsUntilBody(element) {
        const parents = [];
        while (element.parentElement && element.parentElement.tagName !== 'BODY') {
            element = element.parentElement;
            parents.push(element);
        }
        if (element.parentElement && element.parentElement.tagName === 'BODY') {
            parents.push(document.body);
        }
        return parents;
    }

    const targetElement = document.querySelector('.col-sticky');
    if (targetElement) {
        const parents = getParentsUntilBody(targetElement);
        parents.forEach(parent => {
            parent.classList.add('overflow-initial');
        });
        body.classList.add('body-sticky-col');
    }

    // /** Highlight */
    // import hljs from 'highlight.js';
    // import '../../../../scss/front/default/components/highlight/theme.scss';
    // import javascript from 'highlight.js/lib/languages/javascript';
    // /** Then register the languages you need */
    // hljs.registerLanguage('javascript', javascript);
    // hljs.highlightAll();

    /** Animations */

    let animDown = document.querySelector('.down-vertical-parallax');
    let animUp = document.querySelector('.up-vertical-parallax');
    let animRight = document.querySelector('.right-horizontal-parallax');
    let animLeft = document.querySelector('.left-horizontal-parallax');
    if (animDown || animUp || animRight || animLeft) {
        import('./components/animation').then(({default: anim}) => {
            new anim();
        }).catch(error => console.error(error.message));
    }

    let aosEl = document.querySelector('*[data-aos]');
    if (aosEl) {
        import('./components/aos').then(({default: AOS}) => {
            new AOS();
        }).catch(error => console.error(error.message));
    }

    let animateEls = document.querySelectorAll('*[data-animation]')
    if (animateEls.length > 0) {
        import('./components/animate-css').then(({default: animate}) => {
            new animate(animateEls);
        }).catch(error => console.error(error.message));
    }

    import('./components/accessibility').then(({default: Accessibility}) => {
        new Accessibility();
    }).catch(error => console.error(error.message));

    import('../../vendor/components/log-errors').then(({default: Log}) => {
        new Log();
    }).catch(error => console.error(error.message));
});

if (isDebug) {
    new PerformanceObserver((list) => {
        const last = list.getEntries().pop();
        if (last) console.log('LCP', Math.round(last.startTime), last.url || last.element?.currentSrc);
    }).observe({ type: 'largest-contentful-paint', buffered: true });
}