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
    const columns = document.querySelectorAll(".layout-block, .layout-col");
    if (columns.length === 0) return;

    const data = [];

    // 1️⃣ Read phase: Get all measurements first
    columns.forEach(col => {
        col.style.maxWidth = ""; // Reset to read original CSS
        let style = window.getComputedStyle(col);
        let widthValue = col.style.width || style.getPropertyValue("width");
        let parentWidth = col.parentElement.clientWidth || 1;
        let widthPercent = widthValue.includes("%")
            ? parseFloat(widthValue)
            : (parseFloat(style.width) / parentWidth) * 100;

        let marginRight = parseFloat(style.marginRight) || 0;
        let marginLeft = parseFloat(style.marginLeft) || 0;

        if (marginRight > 0 || marginLeft > 0) {
            data.push({
                el: col,
                widthPercent,
                marginPercent: ((marginRight + marginLeft) / parentWidth) * 100
            });
        }
    });

    // 2️⃣ Write phase: Apply all styles together
    data.forEach(item => {
        let newWidthPercent = Math.max(0, item.widthPercent - item.marginPercent);
        item.el.style.maxWidth = `${newWidthPercent}%`;
    });
}

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Prevent unnecessary recalculations on Y-axis resize
let lastWindowWidth = window.innerWidth;

const handleResize = debounce(() => {
    let currentWindowWidth = window.innerWidth;
    if (currentWindowWidth !== lastWindowWidth) {
        adjustColumnsByMargin();
        lastWindowWidth = currentWindowWidth;
    }
}, 150);

// Run at load and on X-axis resize only
window.addEventListener("load", adjustColumnsByMargin);
window.addEventListener("resize", handleResize);

lazyLoadComponent('#main-preloader', () => import(/* webpackPreload: true */'./components/preloader'), (Preloader) => new Preloader());
lazyLoadComponent('.media-block', () => import(/* webpackPreload: true */'./components/medias'), (Medias, els) => new Medias(els), true);
lazyLoadComponent('.splide:not(.thumbnails-slider)', () => import('./components/splide-slider'), (Sliders, els) => new Sliders(els), true);
lazyLoadComponent('.marquee', () => import(/* webpackPreload: true */'./components/marquee'), (Marquees, els) => new Marquees(els), true);
lazyLoadComponent('.entities-filters-form', () => import(/* webpackPreload: true */'./components/entities-filters'), (Filters, els) => new Filters(els));
lazyLoadComponent('.zones-navigation', () => import(/* webpackPreload: true */'./components/zones-navigation'), (Navigations, els) => new Navigations(els));
lazyLoadComponent('.glightbox', () => import(/* webpackPreload: true */'../../vendor/plugins/popup'), (Popups) => new Popups());
lazyLoadComponent('[data-component="masonry"]', () => import(/* webpackPreload: true */'./components/masonry'), (Masonry, els) => new Masonry(els), true);
lazyLoadComponent('.social-wall-wrap', () => import(/* webpackPreload: true */'./components/social-wall'), (socialWalls, els) => new socialWalls(els), true);
lazyLoadComponent('[data-component="counter"]', () => import(/* webpackPreload: true */'./components/counters'), (Counters, els) => new Counters(els), true);
lazyLoadComponent('.parallax', () => import(/* webpackPreload: true */'./components/parallax'), (Parallax, els) => new Parallax(els), true);
lazyLoadComponent('.share-content', () => import(/* webpackPreload: true */'./components/share'), (ShareBoxes) => new ShareBoxes());
lazyLoadComponent('#website-alert', () => import(/* webpackPreload: true */'./components/website-alert'), (Alerts) => new Alerts());
lazyLoadComponent('font', () => import(/* webpackPreload: true */'./components/fonts'), (Fonts) => new Fonts());
lazyLoadComponent('#webmaster-box', () => import(/* webpackPreload: true */'../../vendor/components/webmaster'), (Webmaster, el) => new Webmaster(el));
lazyLoadComponent('#scroll-top-btn', () => import(/* webpackPreload: true */'./components/scroll'), (Scroll) => new Scroll(), true);
lazyLoadComponent('.scroll-link', () => import(/* webpackPreload: true */'./components/scroll'), (Scroll) => new Scroll());
lazyLoadComponent('.newsletter-form-container', () => import(/* webpackPreload: true */'./components/form/newsletter'), (Newsletters) => new Newsletters(), true);
lazyLoadComponent('.step-form-ajax', () => import(/* webpackPreload: true */'./components/form/steps-form'), (StepForm) => new StepForm(), true);
lazyLoadComponent('[data-scroll-bar="1"]', () => import('./components/scrollbar'), (ScrollSpy, els) => new ScrollSpy(els));
lazyLoadComponent('.fixed-news', () => import('./components/fixed-news'), (FixedNews, el) => new FixedNews(el));
lazyLoadComponent('.dropdown-toggle', () => import('../bootstrap/modules/dropdown'), (Dropdown) => new Dropdown());
lazyLoadComponent('.block-scroll-video', () => import('./components/video-scroll'), (Videos) => new Videos());

document.addEventListener('DOMContentLoaded', function () {

    const body = document.body;

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

    const collapse = document.querySelector('.collapse');
    if (collapse) {
        import('../bootstrap/dist/collapse').then(({default: Collapse}) => {
            document.querySelectorAll('.collapse').forEach(function (collapseToggleEl) {
                if (!collapseToggleEl.classList.contains('loaded')) {
                    collapseToggleEl.classList.add('loaded')
                    new Collapse(collapseToggleEl, {
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
        const style = el.getAttribute('style');
        if (!style) return;

        // Reconstruct the style with !important
        const newStyle = style.split(';')
            .filter(d => d.trim() !== '')
            .map(decl => {
                const parts = decl.split(':');
                if (parts.length < 2) return decl;
                const prop = parts.shift().trim();
                const value = parts.join(':').trim();
                return `${prop}: ${value.replace(/\s*!important/g, '')} !important`;
            }).join('; ');

        // Replace the style attribute with the modified version if changed
        if (newStyle !== style) {
            el.setAttribute('style', newStyle);
        }
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

    window.addEventListener('resize', debounce(zoomLevel, 200));

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