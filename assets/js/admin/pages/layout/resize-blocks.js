import route from "../../core/routing";

/**
 * Blocks resize
 */
export default function (Routing) {

    let body = document.body;
    let loader = body.querySelector('#layout-preloader');
    let cols = body.querySelectorAll('.col-sortable');

    cols.forEach(col => {
        let blocksSortable = col.querySelector('.block-sortable');
        if (!blocksSortable) return;

        let blocksContainerWidth = blocksSortable.offsetWidth;
        let gridWidth = Math.floor((blocksContainerWidth - 240) / 12);
        gridWidth = gridWidth < 0 ? Math.floor(blocksContainerWidth / 12) : gridWidth;
        let resizableEls = col.querySelectorAll('.block-resizable');

        resizableEls.forEach(resizable => {
            let blockRow = resizable.querySelector('.block-row');
            let blockHeight = blockRow ? blockRow.offsetHeight : 0;
            resizable.style.height = blockHeight + "px";

            let handle = resizable.querySelector('.btn-resize-block');
            if (!handle) {
                handle = document.createElement('span');
                handle.className = 'btn-resize btn-resize-block ui-resizable-handle ui-resizable-e';
                handle.innerHTML = '<i class="icm-arrow-from-right"></i>';
                resizable.appendChild(handle);
            } else {
                handle.classList.add('ui-resizable-handle', 'ui-resizable-e');
            }

            let startX, startWidth, parent, blockClass, blockId;

            const onMouseMove = (e) => {
                let deltaX = e.clientX - startX;
                let newWidth = startWidth + deltaX;
                let blockSize = Math.round(newWidth / gridWidth);
                let size = Math.min(12, Math.max(1, blockSize));

                parent.classList.remove(blockClass);
                parent.classList.add('col-md-' + size);
                parent.setAttribute('data-size-class', 'col-md-' + size);
                blockClass = 'col-md-' + size;
                resizable.style.height = blockHeight + "px";
            };

            const onMouseUp = (e) => {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);

                if (loader) loader.classList.remove('d-none');

                let finalWidth = startWidth + (e.clientX - startX);
                let blockSize = Math.round(finalWidth / gridWidth);
                let size = Math.min(12, Math.max(1, blockSize));

                fetch(route(Routing, 'admin_block_size', {
                    website: body.dataset.id,
                    block: blockId,
                    size: size
                }) + "?ajax=true")
                .then(response => response.json())
                .then(() => {
                    if (loader) loader.classList.add('d-none');
                })
                .catch(errors => {
                    import('../../core/errors').then(({default: displayErrors}) => {
                        new displayErrors(errors);
                    }).catch(error => console.error(error.message));
                });
            };

            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                startX = e.clientX;
                startWidth = resizable.offsetWidth;
                parent = resizable.parentElement;
                blockClass = parent.getAttribute('data-size-class');
                blockId = parent.getAttribute('data-id');

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });
        });
    });
}