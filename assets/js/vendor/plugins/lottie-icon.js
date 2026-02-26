import lottie from "lottie-web";

/**
 * Lottie
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    document.addEventListener('DOMContentLoaded', function () {

        let icons = document.querySelectorAll('body .ai');

        icons.forEach(function (icon) {

            let name = icon.dataset.name;
            let loop = typeof icon.dataset.loop != 'undefined' ? icon.dataset.loop === 'true' : false;
            let autoplay = typeof icon.dataset.autoplay != 'undefined' ? icon.dataset.autoplay === 'true' : false;
            let hover = typeof icon.dataset.hover != 'undefined' ? icon.dataset.hover === 'true' : false;
            let speed = typeof icon.dataset.speed != 'undefined' ? icon.dataset.speed : .5;
            let parent = icon.closest('.ai-parent');
            let hoverEl = parent ? parent : icon;

            let anim = lottie.loadAnimation({
                container: icon,
                renderer: 'svg',
                loop: loop,
                autoplay: autoplay,
                hover: hover,
                path: '/build/vendor/icons/animated/' + name + '/' + name + '.json'
            });

            lottie.setSpeed(parseFloat(speed));

            if (hover) {

                hoverEl.addEventListener("mouseenter", function () {
                    anim.play();
                });

                hoverEl.addEventListener("mouseleave", function () {
                    anim.stop();
                });
            }
        });
    });
}