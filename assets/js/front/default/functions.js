/**
 * Functions
 *
 * @copyright 2024
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 * @licence under the MIT License (LICENSE.txt)
 */

export function lazyLoadComponent(selector, importFn, init, useObserver = false) {

    const asId = selector.includes('#');
    const els = asId ? document.querySelector(selector) : document.querySelectorAll(selector);
    const haveEls = (asId && els) || (!asId && els.length > 0);

    if (haveEls) {
        if (useObserver && 'IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        importFn().then(m => init(m.default, els)).catch(e => console.error(e.message));
                        observer.disconnect();
                    }
                });
            }, {rootMargin: '100px'});

            if (asId) {
                observer.observe(els);
            } else {
                els.forEach(el => observer.observe(el));
            }
        } else {
            importFn().then(m => init(m.default, els)).catch(e => console.error(e.message));
        }
    }
}

export function isInViewport(el, offset = 0) {
    const bounding = el.getBoundingClientRect(),
        myElementHeight = el.offsetHeight,
        myElementWidth = el.offsetWidth;
    return bounding.top >= -myElementHeight
        && bounding.left >= -myElementWidth
        && bounding.right <= (window.innerWidth + offset || document.documentElement.clientWidth + offset) + myElementWidth
        && bounding.bottom <= (window.innerHeight || document.documentElement.clientHeight) + myElementHeight;
}

export function isElementInMiddleOfScreen(el) {
    let rect = el.getBoundingClientRect();
    let windowHeight = window.innerHeight || document.documentElement.clientHeight;
    let windowWidth = window.innerWidth || document.documentElement.clientWidth;
    let middleY = windowHeight / 2;
    let middleX = windowWidth / 2;
    return (
        rect.top <= middleY && rect.bottom >= middleY &&
        rect.left <= middleX && rect.right >= middleX
    );
}

export function initialPosition(el) {
    let rect = el.getBoundingClientRect();
    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    let scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
    return {
        top: rect.top + scrollTop,
        left: rect.left + scrollLeft
    };
}

export function scrollToEL(el, middle = true, offset = 0) {
    let mainMenu = document.getElementById('main-navigation');
    let offsetTop = mainMenu && (mainMenu.classList.contains('sticky-top') || mainMenu.classList.contains('as-scroll')) ? mainMenu.getBoundingClientRect().height * 1.5 : 0;
    let elOffset = el.getBoundingClientRect().top + window.scrollY;
    let elHeight = el.offsetHeight;
    let windowHeight = window.innerHeight;
    if (elHeight < windowHeight && middle) {
        offset = elOffset - ((windowHeight / 2) - (elHeight / 2));
    } else {
        offset = elOffset;
    }
    offset = offsetTop > 0 ? offset - offsetTop : elOffset;
    window.scrollTo({top: offset, behavior: 'smooth'});
}

export function AjaxPagination(html) {
    let paginationWrap = document.querySelector('.pagination-ajax-wrap');
    if (paginationWrap) {
        let paginationRenderWrap = html.querySelector('.pagination-ajax-wrap');
        let paginationRender = paginationRenderWrap ? paginationRenderWrap.querySelector('.pagination-container') : false;
        document.querySelectorAll('.pagination-container').forEach(item => {
            item.remove();
        });
        if (paginationRender) {
            paginationWrap.insertAdjacentHTML('beforeend', paginationRender.outerHTML);
        }
        import('./components/ajax-pagination').then(({default: AjaxPagination}) => {
            new AjaxPagination()
        }).catch(error => console.error(error.message));
    }
}

export function RemoveAttrsTitle() {
    const selector = "[title]:not([data-bs-toggle])";

    // Use event delegation to avoid attaching many listeners
    const onMouseOver = (e) => {
        const el = e.target.closest(selector);
        if (!el) return;
        const titleValue = el.getAttribute("title");
        if (titleValue) {
            el.setAttribute("data-tmp", titleValue);
            el.removeAttribute("title");
        }
    };

    const onMouseOut = (e) => {
        const el = e.target.closest(selector);
        if (!el) return;
        // Ensure the mouse actually left the element (and not moved to a child)
        const related = e.relatedTarget;
        if (related && el.contains(related)) return;
        const tmpTitle = el.getAttribute("data-tmp");
        if (tmpTitle !== null) {
            el.setAttribute("title", tmpTitle);
            el.removeAttribute("data-tmp");
        }
    };

    document.body.addEventListener("mouseover", onMouseOver, true);
    document.body.addEventListener("mouseout", onMouseOut, true);
}