/**
 * https://parlx-js.github.io/parlx.js/
 */

import Parlx from 'parlx.js'

export default function (parallaxElements) {

    function read(parallaxElement) {
        const styles = window.getComputedStyle(parallaxElement);
        const paddingTop = parseInt(styles.getPropertyValue('padding-top')) || 0;
        const paddingBottom = parseInt(styles.getPropertyValue('padding-bottom')) || 0;
        const elHeight = parallaxElement.offsetHeight;
        const children = parallaxElement.querySelector('.parlx-children');
        if (!children) return null;
        const img = children.querySelector('.parallax-img');
        if (!img) return null;
        const imgHeight = elHeight + (paddingTop * 2) + (paddingBottom * 2);
        return { parallaxElement, children, img, paddingTop, elHeight, imgHeight };
    }

    function write(data) {
        const { children, img, paddingTop, elHeight, imgHeight, parallaxElement } = data;
        children.style.marginTop = `-${paddingTop}px`;
        children.style.setProperty('height', `${elHeight}px`, 'important');
        img.style.setProperty('height', `${imgHeight}px`, 'important');
        // Ensure height is applied even if !important is overridden elsewhere
        img.style.height = `${imgHeight}px`;

        Parlx.init({
            elements: parallaxElement,
            settings: {
                // direction: 'vertical',
                height: `${elHeight}px`,
                // exclude: /(iPod|iPhone|iPad|Android)/
            },
            callbacks: {
                // callbacks...
            }
        });
    }

    function init(list) {
        const batch = [];
        for (let i = 0; i < list.length; i++) {
            const item = read(list[i]);
            if (item) batch.push(item);
        }
        // Single write phase
        batch.forEach(write);
    }

    function debounce(fn, wait) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    init(parallaxElements);
    const onResize = debounce(() => init(parallaxElements), 150);
    window.addEventListener('resize', onResize);
}