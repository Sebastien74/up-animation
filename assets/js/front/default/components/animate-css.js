/**
 * ANIMATE CSS
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
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