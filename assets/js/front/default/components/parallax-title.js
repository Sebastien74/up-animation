/**
 * Parallax main title effect
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (parallaxBlock) {

    $(window).resize(function () {
        parallax();
    });

    $(window).on('scroll', function () {
        parallax();
    });

    function parallax() {

        if (parallaxBlock.length > 0) {

            let yPos = window.pageYOffset;
            let shift = yPos * 0.2 + 'px';
            parallaxBlock.css('bottom', shift);

            let body = $('body');
            let header = body.find('.title-header-block .loader-image-wrapper');
            let headerPosition = header.length > 0 ? header.offset().top + header.outerHeight(true) : header.outerHeight(true);
            let subTitle = body.find('.title-header-block .sub-title');

            if (subTitle.length > 0) {

                let subTitlePosition = subTitle.offset().top + subTitle.outerHeight(true);

                if (subTitlePosition - 45 < headerPosition) {
                    subTitle.addClass('in-header');
                } else {
                    subTitle.removeClass('in-header');
                }
            }
        }
    }
}