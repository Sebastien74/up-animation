import setPositions from "./positions";

/**
 * Sortable activation: Cols order
 */
export default function (Routing) {

    let cols = document.querySelectorAll(".cols-sortable");

    if (cols.length > 0) {
        import('sortablejs').then(({default: Sortable}) => {
            cols.forEach(el => {
                Sortable.create(el, {
                    animation: 150,
                    handle: ".handle-col",
                    draggable: ".col-sortable",
                    ghostClass: "ui-state-highlight",
                    dragClass: "sortable-drag",
                    forceFallback: true,
                    scroll: true,
                    bubbleScroll: true,
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