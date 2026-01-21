/**
 * Tab
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import '../dist/tab';

let tabEls = document.querySelectorAll('[data-bs-toggle="tab"]');
tabEls.forEach(function (triggerEl) {
    triggerEl.addEventListener('shown.bs.tab', event => {
        let content = document.querySelector(event.target.dataset.bsTarget);
        if (content) {
            let masonry = content.querySelectorAll('[data-component="masonry"]');
            if (masonry.length > 0) {
                import('../../default/components/masonry').then(({default: masonryPlugin}) => {
                    new masonryPlugin(masonry);
                }).catch(error => console.error(error.message));
            }
        }
    });
});