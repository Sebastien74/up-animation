import lottie from "lottie-web";

/**
 * Lottie
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    $(function () {

        let icons = $('body').find('.ai');

        icons.each(function () {

            let icon = $(this);
            let name = icon.data('name');
            let loop = typeof icon.data('loop') != 'undefined' ? icon.data('loop') : false;
            let autoplay = typeof icon.data('autoplay') != 'undefined' ? icon.data('autoplay') : false;
            let hover = typeof icon.data('hover') != 'undefined' ? icon.data('hover') : false;
            let speed = typeof icon.data('speed') != 'undefined' ? icon.data('speed') : .5;
            let parent = icon.closest('.ai-parent');
            let hoverEl = parent.length > 0 ? parent[0] : icon[0];

            let anim = lottie.loadAnimation({
                container: icon[0],
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