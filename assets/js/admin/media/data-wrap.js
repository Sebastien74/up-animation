/**
 * Data wrap
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (e, el) {

    let setSingleMedia = function (el, mediasList) {
        mediasList.querySelectorAll('.file').forEach(file => file.classList.remove('active'));
        el.closest('.file').classList.add('active');
    };

    let setMultiples = function (el) {
        let file = el.closest('.file');
        if (file.classList.contains('active')) {
            file.classList.remove('active');
        } else {
            file.classList.add('active');
        }
    };

    let body = document.body;
    let mediasList = body.querySelector('#medias-results');
    let mediasModal = body.querySelector('#medias-library-modal');
    let type = mediasModal ? mediasModal.dataset.type : null;

    if (type === 'single') {
        setSingleMedia(el, mediasList);
    } else if (type === 'multiple') {
        setMultiples(el);
    }
}