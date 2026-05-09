import route from "../../core/routing";

/**
 * Cols resize
 */
export default function (Routing) {

    let body = document.body;
    let loader = body.querySelector('#main-preloader');
    let zones = body.querySelectorAll('.zone');

    zones.forEach(zone => {
        let zoneWidth = zone.offsetWidth;
        let gridWidth = Math.floor(zoneWidth / 12);
        let resizableEls = zone.querySelectorAll('.resizable');

        resizableEls.forEach(resizable => {
            let column = resizable.querySelector('.column');
            let columnHeight = column ? column.offsetHeight : 0;
            resizable.style.height = columnHeight + "px";

            let handle = resizable.querySelector('.btn-resize-col');
            if (!handle) {
                handle = document.createElement('span');
                handle.className = 'btn-resize btn-resize-col ui-resizable-handle ui-resizable-e';
                handle.innerHTML = '<i class="icm-arrow-from-right"></i>';
                resizable.appendChild(handle);
            } else {
                handle.classList.add('ui-resizable-handle', 'ui-resizable-e');
            }

            let startX, startWidth, parent, colClass, colId;
            let lastClientX = 0;
            let rafPending = false;

            const applyMove = () => {
                rafPending = false;
                let deltaX = lastClientX - startX;
                let newWidth = startWidth + deltaX;
                let colSize = Math.round(newWidth / gridWidth);
                let size = Math.min(12, Math.max(1, colSize));
                let nextClass = 'col-md-' + size;

                if (nextClass !== colClass) {
                    parent.classList.remove(colClass);
                    parent.classList.add(nextClass);
                    parent.setAttribute('data-size-class', nextClass);
                    colClass = nextClass;
                }
            };

            const onMouseMove = (e) => {
                lastClientX = e.clientX;
                if (!rafPending) {
                    rafPending = true;
                    requestAnimationFrame(applyMove);
                }
            };

            const onMouseUp = (e) => {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
                document.body.classList.remove('resizing-active');

                if (loader) loader.classList.remove('d-none');

                let finalWidth = startWidth + (e.clientX - startX);
                let colSize = Math.round(finalWidth / gridWidth);
                let size = Math.min(12, Math.max(1, colSize));

                fetch(route(Routing, 'admin_col_size', {
                    website: body.dataset.id,
                    col: colId,
                    size: size
                }) + "?ajax=true")
                .then(response => response.json())
                .then(() => {
                    if (loader) loader.classList.add('d-none');
                    import('./resize-blocks').then(({default: resizeBlocks}) => {
                        new resizeBlocks(Routing);
                    }).catch(error => console.error(error.message));
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
                colClass = parent.getAttribute('data-size-class');
                colId = parent.getAttribute('data-id');
                document.body.classList.add('resizing-active');

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });
        });
    });
}