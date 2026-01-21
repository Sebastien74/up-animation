/**
 * https://parlx-js.github.io/parlx.js/
 */

import Parlx from 'parlx.js'

export default function (parallaxElements) {

    let init = function (parallaxElements) {

        for (let i = 0; i < parallaxElements.length; i++) {

            let parallaxElement = parallaxElements[i];

            parallaxElement.setAttribute('style', 'height: initial');

            let paddingTop = window.getComputedStyle(parallaxElement, null).getPropertyValue('padding-top');
            let paddingBottom = window.getComputedStyle(parallaxElement, null).getPropertyValue('padding-bottom');
            let parallaxElementHeight = parallaxElement.offsetHeight;
            let parallaxChildren = parallaxElement.querySelector('.parlx-children');
            let parallaxImg = parallaxChildren.querySelector('.parallax-img');
            let parallaxImgHeight = parallaxElementHeight + (parseInt(paddingTop.replace('px', '')) * 2) + (parseInt(paddingBottom.replace('px', '')) * 2);

            parallaxChildren.style.marginTop = '-' + paddingTop;
            parallaxChildren.style.height = parallaxElementHeight + 'px !important';
            parallaxImg.style.height = parallaxImgHeight + 'px !important';
            parallaxImg.setAttribute('style', 'height: ' + parallaxImgHeight + 'px');

            Parlx.init({
                elements: parallaxElement,
                settings: {
                    // direction: 'vertical',
                    height: parallaxElementHeight + 'px',
                    // exclude: /(iPod|iPhone|iPad|Android)/
                },
                callbacks: {
                    // callbacks...
                }
            })
        }
    }

    init(parallaxElements)
    window.addEventListener("resize", function (event) {
        init(parallaxElements)
    })
}