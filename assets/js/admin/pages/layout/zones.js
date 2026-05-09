import setPositions from "./positions";

/**
 * Sortable activation: Zones order
 */
export default function (Routing) {
    let el = document.getElementById('zones-sortable');
    if (el) {
        import('sortablejs').then(({default: Sortable}) => {
            Sortable.create(el, {
                animation: 200,
                easing: "cubic-bezier(0.22, 1, 0.36, 1)",
                handle: ".handle-zone",
                draggable: ".zone",
                ghostClass: "ui-state-highlight",
                dragClass: "sortable-drag",
                chosenClass: "sortable-chosen",
                forceFallback: true,
                fallbackTolerance: 5,
                swapThreshold: 0.65,
                invertSwap: true,
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
                    let zonesSortable = Array.from(evt.to.querySelectorAll('.zone'));
                    setPositions(Routing, zonesSortable, 'admin_zones_positions');
                }
            });
        });
    }
}