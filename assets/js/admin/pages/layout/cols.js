import setPositions from "./positions";

/**
 * Sortable activation: Cols order
 */
export default function (Routing) {

    const cols = document.querySelectorAll(".cols-sortable");

    if (cols.length > 0) {
        import('sortablejs').then(({default: Sortable}) => {
            cols.forEach(el => {
                const existing = Sortable.get(el);
                if (existing) {
                    existing.destroy();
                }
                Sortable.create(el, {
                    animation: 200,
                    easing: "cubic-bezier(0.22, 1, 0.36, 1)",
                    handle: ".handle-col",
                    draggable: ".col-sortable",
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
                    onEnd: function() {
                        document.body.classList.remove('sorting-active');
                    },
                    onUpdate: function (evt) {
                        let colsSortable = Array.from(evt.to.querySelectorAll('.col-sortable'));
                        setPositions(Routing, colsSortable, 'admin_cols_positions');
                    }
                });
            });
        });
    }
}