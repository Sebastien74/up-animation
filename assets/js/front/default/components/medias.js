import {isInViewport} from "../functions"

/**
 * Medias
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @doc https://masonry.desandro.com
 */
export default function (blocksMedias) {
    blocksMedias.forEach(block => {
        let picture = block.querySelector('picture');
        if (picture) {
            let img = picture.querySelector('img');
            if (isInViewport(block, 300) && !img.classList.contains('in-viewport')) {
                img.classList.add('in-viewport');
                // img.classList.add('show');
            }
            window.addEventListener('scroll', function (e) {
                if (isInViewport(block, 300) && !img.classList.contains('in-viewport')) {
                    img.classList.add('in-viewport');
                } else {
                    // img.classList.remove('in-viewport');
                }
            });
        }
    });
}