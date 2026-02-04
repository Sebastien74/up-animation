/**
 * Fixed news.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    const STORAGE_KEY = 'fixed_news_closed';

    /**
     * Close with animation then remove from DOM and persist the state.
     * @param {HTMLElement} el
     */
    const closeFixedNews = (el) => {
        el.classList.add('is-closing');
        sessionStorage.setItem(STORAGE_KEY, '1');

        // Remove after transition end (safe even if duration changes in CSS)
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
     * Toggle a class on the fixed news banner when footer is visible in viewport.
     */
    const bindFixedNewsFooterDetection = (requiredRatio = 0.9) => {

        const fixedNews = document.querySelector('.fixed-news');
        const footer = document.querySelector('footer');

        if (!fixedNews || !footer) {
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
         * Update class based on overlap ratio.
         */
        const update = () => {
            ticking = false;

            const fixedRect = fixedNews.getBoundingClientRect();
            const footerRect = footer.getBoundingClientRect();

            const fixedHeight = Math.max(1, fixedRect.height); // avoid division by zero
            const overlap = getVerticalOverlap(fixedRect, footerRect);
            const ratio = overlap / fixedHeight;

            fixedNews.classList.toggle('is-near-footer', ratio >= requiredRatio);
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

    bindFixedNewsFooterDetection(0.9);
}