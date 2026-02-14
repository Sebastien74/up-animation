import {isInViewport} from "../functions";
const AOS = require("aos");

/**
 * AOS Plugin effects
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    if (!prefersReducedMotion.matches) {

        /** To optimize LCP */
        document.querySelectorAll('.aos').forEach(function (el) {
            if (isInViewport(el)) {
                el.classList.remove('aos');
                el.removeAttribute('data-aos');
            }
        });

        // Lazy-load AOS CSS when idle (fallback to timeout)
        const scheduleCss = (cb) => {
            if ('requestIdleCallback' in window) {
                requestIdleCallback(cb, { timeout: 500 });
            } else {
                setTimeout(cb, 0);
            }
        }
        scheduleCss(() => import("aos/dist/aos.css"));

        AOS.init({
            duration: 800,
            once: false
        });

        // Use ResizeObserver instead of polling for height changes
        if ('ResizeObserver' in window) {
            const ro = new ResizeObserver(() => AOS.refresh());
            ro.observe(document.body);
        } else {
            onElementHeightChange(document.body, function () {
                AOS.refresh();
            });
        }

        function onElementHeightChange(elm, callback) {
            let lastHeight = elm.clientHeight;
            let newHeight;
            (function run() {
                newHeight = elm.clientHeight;
                if (lastHeight !== newHeight) callback()
                lastHeight = newHeight;
                if (elm.onElementHeightChangeTimer) {
                    clearTimeout(elm.onElementHeightChangeTimer);
                }
                elm.onElementHeightChangeTimer = setTimeout(run, 200);
            })();
        }
    }
}