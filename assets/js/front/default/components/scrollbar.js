/**
 * Boostrap Collapse.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import Scrollbar from 'smooth-scrollbar';

export default function (els) {
    els.forEach(el => {
        if (!el.classList.contains('loaded')) {
            el.classList.add('loaded');
            Scrollbar.init(el, {
                alwaysShowTracks: true,
            });
        }
    });
}
