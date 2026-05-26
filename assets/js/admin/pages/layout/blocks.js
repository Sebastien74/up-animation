import setPositions from "./positions";
import Tooltip from '../../bootstrap/dist/tooltip';

/**
 * Sortable activation: Blocks order
 * & Block modal
 */
export default function (Routing) {

    /** Blocks order */

    const blocks = document.querySelectorAll(".block-sortable");

    if (blocks.length > 0) {
        import('sortablejs').then(({default: Sortable}) => {
            blocks.forEach(el => {
                const existing = Sortable.get(el);
                if (existing) {
                    existing.destroy();
                }
                Sortable.create(el, {
                    animation: 200,
                    easing: "cubic-bezier(0.22, 1, 0.36, 1)",
                    group: 'blocks',
                    handle: ".handle-block",
                    draggable: ".block",
                    ghostClass: "sortable-placeholder",
                    dragClass: "sortable-drag",
                    chosenClass: "sortable-chosen",
                    forceFallback: true,
                    fallbackTolerance: 5,
                    swapThreshold: 0.65,
                    scroll: true,
                    bubbleScroll: true,
                    scrollSensitivity: 80,
                    scrollSpeed: 12,
                    onStart: function() {
                        document.body.classList.add('sorting-active');
                    },
                    onEnd: function (evt) {
                        document.body.classList.remove('sorting-active');
                        let blocksSortableOriginal = Array.from(evt.to.querySelectorAll('.block'));
                        setPositions(Routing, blocksSortableOriginal, 'admin_blocks_positions', true);
                    }
                });
            });
        });
    }

    /** Blocks modal */
    document.body.addEventListener('click', function(e) {
        let btn = e.target.closest('.open-block-modal');
        if (!btn) return;

        let targetSelector = btn.getAttribute('data-target');
        let modal = document.querySelector(targetSelector);
        let icons = btn.querySelectorAll('.icon-wrap');

        if (modal) {
            if(!modal.classList.contains('active')) {
                modal.classList.add('active');
            }
            else {
                modal.classList.remove('active');
            }
        }

        icons.forEach(icon => {
            icon.classList.toggle('d-none');
            let tooltip = Tooltip.getInstance(icon);
            if (tooltip) {
                tooltip.hide();
            }
        });
    });
}