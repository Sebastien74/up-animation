/**
 * Data wrap
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (e, el) {

    let setSingleMedia = function (el, mediasList) {
        mediasList.find('.file').removeClass('active');
        el.closest('.file').addClass('active');
    };

    let setMultiples = function (el) {
        let file = el.closest('.file');
        if (file.hasClass('active')) {
            file.removeClass('active');
        } else {
            file.addClass('active');
        }
    };

    let body = $('body');
    let mediasList = body.find('#medias-results');
    let mediasModal = body.find('#medias-library-modal');
    let type = mediasModal.data('type');

    if (type === 'single') {
        setSingleMedia(el, mediasList);
    } else if (type === 'multiple') {
        setMultiples(el);
    }
}