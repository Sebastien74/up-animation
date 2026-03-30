/**
 * Init hero parallax.
 */
// export default function initHeroParallax() {
//     const hero = document.querySelector('.template-page > .layout-zone:first-child');
//     const parallax = hero?.querySelector('.carousel-caption');
//
//     // if (!hero || !parallax) {
//     //     return;
//     // }
//
//     parallax.style.setProperty('will-change', 'transform');
//
//     let ticking = false;
//
//     /**
//      * Clamp value between min and max.
//      */
//     const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
//
//     /**
//      * Update parallax offset.
//      */
//     const updateParallax = () => {
//         const scrollY = window.scrollY || window.pageYOffset;
//         const heroHeight = hero.offsetHeight;
//         const maxOffset = 300;
//         const progress = clamp(scrollY / heroHeight, 0, 1);
//         const offset = progress * -maxOffset;
//         parallax.style.transform = `translate3d(0, ${offset}px, 0)`;
//         ticking = false;
//     };
//
//     /**
//      * Schedule update in animation frame.
//      */
//     const requestTick = () => {
//         if (ticking) {
//             return;
//         }
//         ticking = true;
//         window.requestAnimationFrame(updateParallax);
//     };
//
//     updateParallax();
//     window.addEventListener('scroll', requestTick, { passive: true });
//     window.addEventListener('resize', requestTick);
// }