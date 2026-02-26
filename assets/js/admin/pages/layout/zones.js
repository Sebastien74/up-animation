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
                onUpdate: function (evt) {
                    let zonesSortable = Array.from(evt.to.querySelectorAll('.zone'));
                    setPositions(Routing, zonesSortable, 'admin_zones_positions');
                }
            });
        });
    }
}