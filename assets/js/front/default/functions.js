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
    const bounding = el.getBoundingClientRect();
    const windowHeight = (window.innerHeight || document.documentElement.clientHeight);
    const windowWidth = (window.innerWidth || document.documentElement.clientWidth);
    return bounding.top >= -(bounding.height || 0)
        && bounding.left >= -(bounding.width || 0)
        && bounding.right <= (windowWidth + offset) + (bounding.width || 0)
        && bounding.bottom <= windowHeight + (bounding.height || 0);
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
    const rect = el.getBoundingClientRect();
    let offsetTop = mainMenu && (mainMenu.classList.contains('sticky-top') || mainMenu.classList.contains('as-scroll')) ? mainMenu.getBoundingClientRect().height * 1.5 : 0;
    let elOffset = rect.top + window.scrollY;
    let elHeight = rect.height;
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
    document.querySelectorAll("[title]:not([data-bs-toggle])").forEach(el => {
        el.addEventListener("mouseover", () => {
            const titleValue = el.getAttribute("title");
            if (titleValue) {
                el.setAttribute("data-tmp", titleValue);
                el.removeAttribute("title");
            }
        });
        el.addEventListener("mouseleave", () => {
            const tmpTitle = el.getAttribute("data-tmp");
            if (tmpTitle !== null) {
                el.setAttribute("title", tmpTitle);
                el.removeAttribute("data-tmp");
            }
        });
    });
}

/**
 * Debounce function.
 * @param {Function} func
 * @param {number} wait
 * @returns {Function}
 */
export function debounce(func, wait) {
    let timeout;
    return function (...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}