import Sortable from 'sortablejs';

export default function () {

    if (document.querySelector('.nestable-list-container')) {
        import('../../../scss/admin/lib/tree-sortable.scss');
    }

    const body = document.body;
    const isActive = body.classList.contains('editor');
    const nestIntentOffset = 34;
    const outdentIntentOffset = 24;
    let pointerX = 0;

    if (!isActive && document.querySelector('.nestable-list-container')) {
        console.warn('Nestable container present but isActive is false (missing .editor class on body)');
    }

    const serialize = function (container) {
        const items = [];
        container.querySelectorAll(':scope > .dd-item').forEach(el => {
            const item = {
                id: el.dataset.id
            };
            const sublist = el.querySelector(':scope > .dd-list');
            if (sublist) {
                const children = serialize(sublist);
                if (children.length > 0) {
                    item.children = children;
                }
            }
            items.push(item);
        });
        return items;
    };

    document.querySelectorAll('.nestable-list-container').forEach(function (el) {

        const elId = el.getAttribute('id');
        const outputFieldSelector = el.dataset.outputField;
        const limit = el.dataset.limit;

    /** Tree Sortable */
    const updateOutput = function () {

        const windowLoadEl = body.querySelector('.tree-sortable-window-load');
        const isFirstLoad = !windowLoadEl;
        const container = el.querySelector('.dd-list');
        const outputSelector = el.dataset.output || outputFieldSelector;
        const output = document.querySelector(outputSelector);
        const form = el.querySelector('form.nestable-outpout-form');
        const preloader = body.querySelector('#nestable-list-preloader');

        if (output && form && container) {

            const serializedData = JSON.stringify(serialize(container));
            output.value = serializedData;

            const formData = new FormData();
            const outputName = output.getAttribute('name');
            if (outputName) {
                formData.append(outputName, serializedData);
            }

            // Also include other hidden fields from the form if any
            form.querySelectorAll('input[type="hidden"]').forEach(input => {
                const name = input.getAttribute('name');
                if (name && name !== outputName) {
                    formData.append(name, input.value);
                }
            });

            if (!isFirstLoad && preloader) {
                preloader.classList.remove('d-none');
            }

            fetch(form.getAttribute('action') || window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw response;
                    }
                    return response.json();
                })
                .then(response => {
                    if (!isFirstLoad && preloader) {
                        preloader.classList.add('d-none');
                    }
                })
                .catch(errors => {
                    if (!isFirstLoad && preloader) {
                        preloader.classList.add('d-none');
                    }
                    /** Display errors */
                    import('../core/errors').then(({default: displayErrors}) => {
                        new displayErrors(errors);
                    }).catch(error => console.error(error.message));
                });
        }
    };

        if (isActive) {

            const nestableEl = document.getElementById(elId);

            document.querySelectorAll('.custom-control-label').forEach(label => {
                label.addEventListener('click', function (e) {
                    if (!nestableEl.classList.contains('disabled-nestable')) {
                        nestableEl.classList.add('disabled-nestable');
                    }
                });
            });

            let pendingNestTarget = null;

            const initSortable = function (listEl) {

                const isNestedPastLimit = function (targetList) {
                    if (!limit) {
                        return false;
                    }

                    let depth = 1;
                    let parent = targetList.parentElement;
                    while (parent && parent !== nestableEl) {
                        if (parent.classList.contains('dd-list')) {
                            depth++;
                        }
                        parent = parent.parentElement;
                    }

                    return depth >= parseInt(limit, 10);
                };

                const cleanupEmptyLists = function () {
                    nestableEl.querySelectorAll('.dd-list').forEach(list => {
                        if (list !== nestableEl.querySelector(':scope > .dd-list') && !list.querySelector(':scope > .dd-item')) {
                            if (list.sortable) {
                                list.sortable.destroy();
                            }
                            list.remove();
                        }
                    });
                };

                const outdentItem = function (itemEl, fromList) {
                    const parentItem = fromList.closest('.dd-item');
                    const parentList = parentItem ? parentItem.parentElement : null;

                    if (parentItem && parentList && parentList.classList.contains('dd-list')) {
                        parentItem.insertAdjacentElement('afterend', itemEl);
                        cleanupEmptyLists();
                        updateOutput();
                    }
                };

                const ensureChildList = function (itemEl) {
                    let sublist = itemEl.querySelector(':scope > .dd-list');
                    if (!sublist) {
                        sublist = document.createElement('ol');
                        sublist.classList.add('dd-list');
                        itemEl.appendChild(sublist);
                    }
                    if (!sublist.sortable) {
                        initSortable(sublist);
                    }
                    return sublist;
                };

                listEl.sortable = new Sortable(listEl, {
                    group: 'nested',
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    invertedSwapThreshold: 0.25,
                    invertSwap: true,
                    handle: '.dd-handle',
                    draggable: '.dd-item',
                    ghostClass: 'dd-placeholder',
                    scroll: true,
                    forceFallback: true,
                    fallbackTolerance: 5,
                    onStart: function (evt) {
                        pendingNestTarget = null;
                        if (evt.originalEvent) {
                            pointerX = evt.originalEvent.clientX;
                        }
                    },
                    onAdd: function (evt) {

                        const targetList = evt.to;
                        // Ensure newly added children list in the item is also sortable
                        targetList.querySelectorAll('.dd-list').forEach(list => {
                            if (!list.sortable) {
                                initSortable(list);
                            }
                        });
                        const itemEl = evt.item;
                        const sublist = itemEl.querySelector('.dd-list');
                        if (sublist && !sublist.sortable) {
                            initSortable(sublist);
                        }
                        cleanupEmptyLists();
                        updateOutput();
                    },
                    onEnd: function (evt) {
                        if (evt.originalEvent) {
                            pointerX = evt.originalEvent.clientX;
                        }

                        if (pendingNestTarget && !evt.item.contains(pendingNestTarget)) {
                            const targetRect = pendingNestTarget.getBoundingClientRect();
                            const wantsChild = pointerX > targetRect.left + nestIntentOffset;
                            const targetChildList = pendingNestTarget.querySelector(':scope > .dd-list');

                            if (wantsChild && targetChildList && evt.item.parentElement !== targetChildList) {
                                targetChildList.appendChild(evt.item);
                                pendingNestTarget = null;
                                cleanupEmptyLists();
                                updateOutput();
                                return;
                            }
                        }

                        pendingNestTarget = null;
                        const currentList = evt.item.parentElement;
                        const parentItem = currentList.closest('.dd-item');
                        const currentRect = currentList.getBoundingClientRect();

                        if (parentItem && pointerX < currentRect.left + outdentIntentOffset) {
                            outdentItem(evt.item, currentList);
                            return;
                        }

                        cleanupEmptyLists();
                        updateOutput();
                    },
                    onMove: function (evt) {
                        if (evt.originalEvent) {
                            pointerX = evt.originalEvent.clientX;
                        }

                        const targetItem = evt.related;
                        if (targetItem && targetItem.classList.contains('dd-item')) {
                            const rect = targetItem.getBoundingClientRect();
                            const wantsChild = pointerX > rect.left + nestIntentOffset;

                            if (wantsChild && !isNestedPastLimit(evt.to) && evt.dragged && !evt.dragged.contains(targetItem)) {
                                pendingNestTarget = targetItem;
                                ensureChildList(targetItem);
                            } else {
                                pendingNestTarget = null;
                            }
                        }
                    },
                    emptyInsertThreshold: 45
                });
            };

            nestableEl.querySelectorAll('.dd-list').forEach(list => {
                initSortable(list);
            });

            const observer = new MutationObserver(() => {
                nestableEl.querySelectorAll('.dd-list').forEach(list => {
                    if (!list.sortable) {
                        initSortable(list);
                    }
                });
            });
            observer.observe(nestableEl, { childList: true, subtree: true });
        }

        /** To use loader only if not the first load */
        el.insertAdjacentHTML('beforeend', '<span class="tree-sortable-window-load"></span>');
    });

    /** Collapsed items event */
    document.querySelectorAll('.btn-collapsed-group').forEach(function (group) {

        const collapseBtn = group.querySelector('.collapse-btn');
        const expandBtn = group.querySelector('.expand-btn');
        const parent = group.closest('.parent-row');

        if (collapseBtn && expandBtn && parent) {
            collapseBtn.addEventListener('click', function () {
                if (!collapseBtn.classList.contains('d-flex')) {
                    collapseBtn.classList.remove('d-none');
                    collapseBtn.classList.add('d-flex');
                    expandBtn.classList.add('d-none');
                    expandBtn.classList.remove('d-flex');
                } else {
                    collapseBtn.classList.add('d-none');
                    collapseBtn.classList.remove('d-flex');
                    expandBtn.classList.remove('d-none');
                    expandBtn.classList.add('d-flex');
                }
                parent.classList.toggle('dd-collapsed');
            });

            expandBtn.addEventListener('click', function () {
                collapseBtn.click();
            });
        }
    });

    /** Expand all */
    body.addEventListener('click', function (e) {
        if (e.target && e.target.id === 'nestable-expand-all') {
            const expandBtns = body.querySelectorAll('.expand-btn');
            expandBtns.forEach(btn => {
                btn.click();
                btn.classList.add('active');
            });
            e.target.classList.add('d-none');
            const collapseAll = document.getElementById('nestable-collapse-all');
            if (collapseAll) {
                collapseAll.classList.remove('d-none');
            }
        }
    });

    /** Collapse all */
    body.addEventListener('click', function (e) {
        if (e.target && e.target.id === 'nestable-collapse-all') {
            const collapseBtns = body.querySelectorAll('.collapse-btn');
            const expandBtns = body.querySelectorAll('.expand-btn');
            collapseBtns.forEach(btn => btn.click());
            expandBtns.forEach(btn => btn.classList.remove('active'));
            e.target.classList.add('d-none');
            const expandAll = document.getElementById('nestable-expand-all');
            if (expandAll) {
                expandAll.classList.remove('d-none');
            }
        }
    });
}
