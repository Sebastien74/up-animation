/** https://github.com/dev-florian/fullib-js */

import Animation from "fullib-js/src/js/Basic/Animation";

export default function () {

    const windowWidth = window.innerWidth;
    const isScrollMobile = false;
    const breakpoint = 991;

    new Animation({
        elems: '.down-vertical-parallax',
        scroll: true, /** default true */
        start: '0%', /** default 0% */
        end: '100%', /** default 100% */
        measure: 'px', /** default px */
        isScrollMobile: isScrollMobile,
        mobileBreakpoint: breakpoint,
        from: {
            y: windowWidth > breakpoint ? -200 : -100
        },
        to: {
            y: windowWidth > breakpoint ? 30 : 15,
        }
    });

    new Animation({
        elems: '.up-vertical-parallax',
        scroll: true, /** default true */
        start: '0%', /** default 0% */
        end: '100%', /** default 100% */
        measure: 'px', /** default px */
        isScrollMobile: isScrollMobile,
        mobileBreakpoint: breakpoint,
        from: {
            y: windowWidth > breakpoint ? 200 : 100
        },
        to: {
            y: windowWidth > breakpoint ? -30 : -15,
        }
    });

    new Animation({
        elems: '.left-horizontal-parallax',
        scroll: true, /** default true */
        delay: windowWidth > breakpoint ? 0 : 0, /** default true */
        start: '0%', /** default 0% */
        end: windowWidth > breakpoint ? '100%' : '75%', /** default 100% */
        measure: 'px', /** default px */
        isScrollMobile: isScrollMobile,
        mobileBreakpoint: breakpoint,
        from: {
            x: windowWidth > breakpoint ? 100 : windowWidth
        },
        to: {
            x: 0,
        }
    });

    new Animation({
        elems: '.right-horizontal-parallax',
        scroll: true, /** default true */
        delay: windowWidth > breakpoint ? 0 : 0, /** default true */
        start: '0%', /** default 0% */
        end: windowWidth > breakpoint ? '100%' : '75%', /** default 100% */
        measure: 'px', /** default px */
        isScrollMobile: isScrollMobile,
        mobileBreakpoint: breakpoint,
        from: {
            x: windowWidth > breakpoint ? -100 : -windowWidth
        },
        to: {
            x: 0
        }
    });
}