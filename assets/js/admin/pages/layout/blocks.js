import setPositions from "./positions";

/**
 * Sortable activation : Blocks order
 * & Block modal
 */
export default function (Routing) {

    /** Blocks order */

    let blocks = $(".block-sortable");

    if (typeof blocks !== 'undefined') {

        let sortableBlock = blocks.sortable({
            placeholder: "highlight-block",
            connectWith: ".block-sortable",
            items: '.block',
            handle: ".handle-block",
            start: function (event, ui) {
                ui.placeholder.height(ui.item.height());
                ui.placeholder.width(ui.item.width());
            },
            change: function (event, ui) {
                let width = $(event.target).closest('.portlet-content').innerWidth() - 30;
                ui.item.width(width);
                ui.placeholder.width(width);
            },
            update: function (event, ui) {
                let blocksSortableOriginal = $(event.target).find('.block');
                setPositions(Routing, blocksSortableOriginal, 'admin_blocks_positions', true);
                event.stopImmediatePropagation();
            }
        });

        sortableBlock.disableSelection();
    }

    /** Blocks modal */
    $('body').on('click', '.open-block-modal', function() {
        let btn = $(this);
        let modal = $(btn.data('target'));
        let icons = btn.find('.icon-wrap');
        if(!modal.hasClass('active')) {
            modal.addClass('active');
        }
        else {
            modal.removeClass('active');
        }
        icons.toggleClass('d-none');
        icons.tooltip('hide');
    });
}