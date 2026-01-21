import setPositions from "./positions";

/**
 * Sortable activation : Cols order
 */
export default function (Routing) {

    let cols = $(".cols-sortable");

    if (typeof cols !== 'undefined') {

        let sortableCol = cols.sortable({
            placeholder: "ui-state-highlight",
            items: '.col-sortable',
            handle: ".handle-col",
            start: function (e, ui) {
                ui.placeholder.height(ui.item.height());
                ui.placeholder.width(ui.item.width());
            },
            update: function (event, ui) {
                let colsSortable = ui.item.parent().find('.col-sortable');
                setPositions(Routing, colsSortable, 'admin_cols_positions');
                event.stopImmediatePropagation();
            }
        });

        sortableCol.disableSelection();
    }
}