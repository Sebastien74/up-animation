/**
 * GLightbox Popup
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 * https://github.com/biati-digital/glightbox
 */

import GLightbox from 'glightbox';

export default function () {

    setTimeout(() => {
        import('../../../scss/vendor/components/_glightbox.scss');
    }, 50);

    GLightbox({
        selector: '.glightbox',
        touchNavigation: true,
        loop: true,
        height: '70%',
        autoplayVideos: true
    });
}