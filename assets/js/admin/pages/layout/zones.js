import setPositions from "./positions";

/**
 * Sortable activation : Zones order
 */
export default function (Routing) {
    let sortableZone = $('#zones-sortable').sortable({
        placeholder: "ui-state-highlight",
        items: '.zone',
        handle: ".handle-zone",
        start: function (e, ui) {
            ui.placeholder.height(ui.item.height());
        },
        update: function (event, ui) {
            let zonesSortable = ui.item.parent().find('.zone');
            setPositions(Routing, zonesSortable, 'admin_zones_positions');
            event.stopImmediatePropagation();
        }
    });
    sortableZone.disableSelection();
}