import setPositions from "./positions";

/**
 * Sortable activation: Zones order
 */
export default function (Routing) {
    let el = document.getElementById('zones-sortable');
    if (el) {
        import('sortablejs').then(({default: Sortable}) => {
            Sortable.create(el, {
                animation: 150,
                handle: ".handle-zone",
                draggable: ".zone",
                ghostClass: "ui-state-highlight",
                dragClass: "sortable-drag",
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