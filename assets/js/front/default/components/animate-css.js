/**
 * ANIMATE CSS
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 * Note: this module assigns the `animate__animated` and `animate__<effect>` classes
 * but does NOT load the animate.css stylesheet by default. Animations stay silent
 * unless the CSS is imported. To activate site-wide animations, uncomment the
 * dynamic import below — webpack will produce an async chunk loaded only when this
 * module itself is loaded (i.e. when at least one [data-animation] element exists).
 *
 *   import('animate.css/animate.min.css');
 */

import {isInViewport} from "../functions"

export default function (animateEls) {

    animateEls.forEach(function (element) {
        let onload = element.dataset.onload && (element.dataset.onload === 'true' || element.dataset.onload === '1') ? element.dataset.onload : false;
        let onScroll = element.dataset.onscroll && (element.dataset.onscroll === 'true' || element.dataset.onscroll === '1') ? element.dataset.onscroll : false;
        if (onload) {
        } else if (onScroll) {
            if (isInViewport(element) && !element.classList.contains('animate__animated')) {
                animate(element);
            } else {
                window.addEventListener('scroll', () => {
                    if (isInViewport(element) && !element.classList.contains('animate__animated')) {
                        animate(element);
                    }
                })
            }
        } else {
            element.addEventListener("mouseenter", function () {
                element.classList.add('animate__animated');
                element.classList.add('animate__' + element.dataset.animation);
                setTimeout(function () {
                    element.addEventListener('mouseout', onMouseOut, false);
                }, 50)
            }, false)
        }
    });

    function animate(element) {
        let delay = element.dataset.delay ? parseInt(element.dataset.delay) : false;
        if (delay) {
            setTimeout(function () {
                element.classList.add('animate__animated');
                element.classList.add('animate__' + element.dataset.animation);
            }, delay);
        } else {
            element.classList.add('animate__animated');
            element.classList.add('animate__' + element.dataset.animation);
        }
    }

    function onMouseOut(event) {
        let el = event.toElement || event.relatedTarget;
        if (el) {
            let currentAnimations = document.querySelectorAll('.animate__animated');
            currentAnimations.forEach(function (animation) {
                animation.classList.remove('animate__animated');
                animation.classList.remove('animate__' + animation.dataset.animation);
            });
        }
    }
}