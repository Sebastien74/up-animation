import flatSortable from "./flat-sortable";

/**
 * Prototypes sortable — flat list (block / block-group).
 * DIV-based markup, driven by SortableJS (markup-agnostic).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    const setPositions = function (elId) {

        let progress = 0;
        const element = document.getElementById(elId);
        const elements = element.querySelectorAll('.handle-item-prototype');

        const setPosition = function () {
            const handle = element.querySelector('.handle-item-prototype:not(.generate)');
            if (!handle) {
                return;
            }
            const path = handle.dataset.path;
            let url = path + '?ajax=true&position=';
            if (path.indexOf('?') > -1) {
                url = path + '&ajax=true';
            }
            const xHttp = new XMLHttpRequest();
            xHttp.open("GET", url + '&position=' + handle.dataset.position, true);
            xHttp.send();
            xHttp.onload = function () {
                if (this.readyState === 4 && this.status === 200) {
                    progress++;
                    handle.classList.add('generate');
                    if (progress === elements.length) {
                        window.location.replace(window.location.href);
                    } else {
                        setPosition();
                    }
                }
            };
        };
        setPosition();
    };

    const sortables = document.querySelectorAll('.prototype-sortable');

    sortables.forEach(function (el) {

        const elId = el.getAttribute('id');
        const asDeletable = el.querySelectorAll('.swal-delete-link');
        const loader = el.querySelector('.prototype-preloader, .app-loader');
        const draggableSelector = el.querySelectorAll('.prototype-block-group').length > 0
            ? '.prototype-block-group'
            : '.prototype-block';

        if (asDeletable.length > 0) {
            el.querySelectorAll('.prototype-block').forEach(item => item.classList.add('as-deletable'));
        }

        flatSortable(el, {
            handle: ".handle-item-prototype",
            draggable: draggableSelector,
            onUpdate: function () {

                if (loader) {
                    loader.classList.remove('d-none');
                }

                const items = el.querySelectorAll('.handle-item-prototype');

                if (typeof bootstrap !== 'undefined') {
                    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                    tooltips.forEach(t => {
                        const instance = bootstrap.Tooltip.getInstance(t);
                        if (instance) instance.hide();
                    });
                }

                items.forEach(function (itemEl, i) {
                    itemEl.setAttribute('data-position', (i + 1).toString());
                    itemEl.classList.add('in-progress');
                });

                const firstItem = items[0];
                if (firstItem) {
                    const form = firstItem.closest('form');
                    if (form) {
                        const xHttp = new XMLHttpRequest();
                        xHttp.open("POST", form.getAttribute('action') + '?ajax=true', true);
                        xHttp.send(new FormData(form));
                        xHttp.onload = function () {
                            if (this.readyState === 4 && this.status === 200) {
                                setPositions(elId);
                            }
                        };
                    }
                } else {
                    setPositions(elId);
                }
            }
        });
    });
}
