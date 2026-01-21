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

        setTimeout(() => {
            import("aos/dist/aos.css");
        }, 0.1);

        AOS.init({
            duration: 800,
            once: false
        });

        onElementHeightChange(document.body, function () {
            AOS.refresh();
        });

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