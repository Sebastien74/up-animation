/**
 * Fixed news.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    const STORAGE_KEY = 'fixed_news_closed';

    /**
     * Close with animation, then remove from DOM and persist the state.
     * @param {HTMLElement} el
     */
    const closeFixedNews = (el) => {
        el.classList.add('is-closing');
        sessionStorage.setItem(STORAGE_KEY, '1');

        // Remove after the transition end (safe even if duration changes in CSS)
        const onEnd = (ev) => {
            if (ev.target !== el) return;
            el.removeEventListener('transitionend', onEnd);
            el.remove();
        };

        el.addEventListener('transitionend', onEnd);

        // Fallback (if transitionend doesn't fire)
        window.setTimeout(() => {
            if (document.contains(el)) el.remove();
        }, 400);
    };

    /**
     * Initialize fixed news behavior.
     */
    const initFixedNews = () => {
        const fixedNews = document.querySelector('a.fixed-news');
        if (!fixedNews) return;

        // If already closed during this session, remove immediately.
        if (sessionStorage.getItem(STORAGE_KEY) === '1') {
            fixedNews.remove();
            return;
        }

        const btnClose = fixedNews.querySelector('.btn-close');
        if (!btnClose) return;

        btnClose.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            closeFixedNews(fixedNews);
        }, true);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFixedNews, { once: true });
    } else {
        initFixedNews();
    }

    /**
     * Toggle a class on elements when the footer is visible in the viewport.
     *
     * @param {string} selector
     * @param {number} requiredRatio
     * @param {Object} classConfig
     */
    const bindFooterDetection = (selector, requiredRatio = 0.9, classConfig = {}) => {

        const elements = document.querySelectorAll(selector);
        const footer = document.querySelector('footer');

        if (elements.length === 0 || !footer) {
            return;
        }

        let ticking = false;

        /**
         * Compute vertical overlap between two DOMRects.
         *
         * @param {DOMRect} a
         * @param {DOMRect} b
         * @returns {number} overlap height in px
         */
        const getVerticalOverlap = (a, b) => {
            const top = Math.max(a.top, b.top);
            const bottom = Math.min(a.bottom, b.bottom);
            return Math.max(0, bottom - top);
        };

        /**
         * Update class based on an overlap ratio.
         */
        const update = () => {
            ticking = false;
            const footerRect = footer.getBoundingClientRect();

            elements.forEach(el => {
                const rect = el.getBoundingClientRect();
                const height = Math.max(1, rect.height); // avoid division by zero
                const overlap = getVerticalOverlap(rect, footerRect);
                const ratio = overlap / height;
                const isNear = ratio >= requiredRatio;

                el.classList.toggle('is-near-footer', isNear);

                if (classConfig.add || classConfig.remove) {
                    const toAdd = Array.isArray(classConfig.add) ? classConfig.add : [classConfig.add];
                    const toRemove = Array.isArray(classConfig.remove) ? classConfig.remove : [classConfig.remove];

                    if (isNear) {
                        toRemove.forEach(cls => cls && el.classList.remove(cls));
                        toAdd.forEach(cls => cls && el.classList.add(cls));
                    } else {
                        toAdd.forEach(cls => cls && el.classList.remove(cls));
                        toRemove.forEach(cls => cls && el.classList.add(cls));
                    }
                }
            });
        };

        /**
         * Throttle updates with rAF for smoothness.
         */
        const requestUpdate = () => {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(update);
        };

        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);

        // Initial state
        requestUpdate();
    };

    bindFooterDetection('.fixed-news', 0.1);
    // bindFooterDetection('.product-contact-btn', 0.1, {
    //     add: 'btn-secondary',
    //     remove: 'btn-primary'
    // });
}