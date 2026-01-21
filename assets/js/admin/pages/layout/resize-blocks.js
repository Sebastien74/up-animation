import route from "../../core/routing";

/**
 * Blocks resize
 */
export default function (Routing) {

    let body = $('body');
    let loader = body.find('#layout-preloader');
    let cols = body.find('.col-sortable');

    cols.each(function () {

        let col = $(this);
        let blocksContainerWidth = parseInt(col.find('.block-sortable').width());
        let gridWidth = Math.floor((blocksContainerWidth - 240) / 12);
        gridWidth = gridWidth < 0 ? Math.floor((blocksContainerWidth) / 12) : gridWidth;
        let resizableEls = col.find('.block-resizable');

        resizableEls.each(function () {

            let resizable = $(this);
            let body = $('body');
            let blockHeight = resizable.find('.block-row').height();

            resizable.css('height', blockHeight + "px");

            resizable.resizable({
                handles: 'e',
                containment: "#block-sortable-" + col.data('id'),
                grid: gridWidth,
                resize: function (event, ui) {

                    let parent = resizable.parent();
                    let blockClass = parent.attr('data-size-class');
                    let blockSize = Math.floor(ui.size.width / gridWidth);
                    let size = blockSize > 12 ? 12 : blockSize;

                    if(size > 0) {
                        parent.removeClass(blockClass).addClass('col-md-' + size).attr('data-size-class', 'col-md-' + size);
                        resizable.css('height', blockHeight + "px");
                    }
                },
                stop: function (event, ui) {

                    loader.removeClass('d-none');

                    let parent = resizable.parent();
                    let blockClass = parent.attr('data-size-class');
                    let blockId = parent.attr('data-id');
                    let blockSize = Math.floor(ui.size.width / gridWidth);
                    let size = blockSize > 12 ? 12 : blockSize;

                    if(size > 0) {

                        parent.removeClass(blockClass).addClass('col-md-' + size).attr('data-size-class', 'col-md-' + size);
                        resizable.css('height', blockHeight + "px");

                        $.ajax({
                            url: route(Routing, 'admin_block_size', {
                                website: body.data('id'),
                                block: blockId,
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
                            },
                            error: function (errors) {
                                /** Display errors */
                                import('../../core/errors').then(({default: displayErrors}) => {
                                    new displayErrors(errors);
                                }).catch(error => console.error(error.message));
                            }
                        });
                    }

                    event.stopImmediatePropagation();
                    return false;
                }
            });
        });
    });
}