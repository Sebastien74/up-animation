import route from "../../core/routing";

/**
 * Cols resize
 */
export default function (Routing) {

    let body = $('body');
    let loader = body.find('#layout-preloader');
    let zones = body.find('.zone');

    zones.each(function () {

        let zone = $(this);
        let zoneWidth = parseInt(zone.width());
        let gridWidth = Math.floor(zoneWidth / 12);
        let resizableEls = zone.find('.resizable');

        resizableEls.each(function () {

            let resizable = $(this);
            let body = $('body');
            let columnHeight = resizable.find('.column').height();

            resizable.css('height', columnHeight + "px");

            resizable.resizable({
                handles: 'e',
                containment: ".zone",
                grid: gridWidth,
                resize: function (event, ui) {
                    let parent = resizable.parent();
                    let colClass = parent.attr('data-size-class');
                    let colSize = Math.ceil(ui.size.width / gridWidth);
                    let size = colSize > 12 ? 12 : colSize;
                    parent.removeClass(colClass).addClass('col-md-' + size).attr('data-size-class', 'col-md-' + size);
                    resizable.css('height', columnHeight + "px");
                },
                stop: function (event, ui) {

                    loader.removeClass('d-none');

                    let parent = resizable.parent();
                    let colClass = parent.attr('data-size-class');
                    let colId = parent.attr('data-id');
                    let colSize = Math.ceil(ui.size.width / gridWidth);
                    let size = colSize > 12 ? 12 : colSize;

                    parent.removeClass(colClass).addClass('col-md-' + size).attr('data-size-class', 'col-md-' + size);
                    resizable.css('height', columnHeight + "px");

                    $.ajax({
                        url: route(Routing, 'admin_col_size', {
                            website: body.data('id'),
                            col: colId,
                            size: size
                        }) + "?ajax=true",
                        type: "GET",
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        async: true,
                        beforeSend: function () {
                        },
                        success: function (response) {
                            loader.addClass('d-none');
                            import('./resize-blocks').then(({default: resizeBlocks}) => {
                                new resizeBlocks(Routing);
                            }).catch(error => console.error(error.message));
                        },
                        error: function (errors) {
                            /** Display errors */
                            import('../../core/errors').then(({default: displayErrors}) => {
                                new displayErrors(errors);
                            }).catch(error => console.error(error.message));
                        }
                    });

                    event.stopImmediatePropagation();
                    return false;
                }
            });
        });
    });
}