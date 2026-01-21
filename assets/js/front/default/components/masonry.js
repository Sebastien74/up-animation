import Masonry from 'masonry-layout'

/**
 * Masonry
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @doc https://masonry.desandro.com
 */
export default function (grids) {
    let screenWidth = window.screen.width;
    if (screenWidth > 767) {
        for (let i = 0, len = grids.length; i < len; i++) {
            let gridEl = grids[i];
            let grid = new Masonry(gridEl, {
                itemSelector: '.grid-item'
            });
        }
    }
}