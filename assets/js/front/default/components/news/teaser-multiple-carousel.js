import '../../../../../scss/front/default/components/news/_carousel-multiple.scss';

/**
 * News vendor
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (body, carousels) {

    carousels.each(function () {

        let carousel = $(this);
        let interval = carousel.data('interval') ? carousel.data('interval') : 5000;
        let itemPerSlide = carousel.data('per-slide') ? carousel.data('per-slide') : 3;
        itemPerSlide = $(window).width() < 992 ? 2 : itemPerSlide;

        carousel.find('.carousel-item').each(function () {

            let minPerSlide = itemPerSlide;
            let next = $(this).next();

            if (!next.length) {
                next = $(this).siblings(':first');
            }

            next.children(':first-child').clone().appendTo($(this));

            for (let i = 0; i < minPerSlide; i++) {
                next = next.next();
                if (!next.length) {
                    next = $(this).siblings(':first');
                }
                next.children(':first-child').clone().appendTo($(this));
            }
        });

        carousel.carousel({
            interval: interval
        });
    });
}