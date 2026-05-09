import 'nestable3';

export default function () {

    if (document.querySelector('.nestable-list-container')) {
        import('../../../scss/admin/lib/tree-sortable.scss');
    }

    const body = document.body;
    const isActive = body.classList.contains('editor');

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

        const outputFieldSelector = el.dataset.outputField;
        const limit = el.dataset.limit;

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
                    .then(() => {
                        if (!isFirstLoad && preloader) {
                            preloader.classList.add('d-none');
                        }
                    })
                    .catch(errors => {
                        if (!isFirstLoad && preloader) {
                            preloader.classList.add('d-none');
                        }
                        import('../core/errors').then(({default: displayErrors}) => {
                            new displayErrors(errors);
                        }).catch(error => console.error(error.message));
                    });
            }
        };

        if (isActive) {

            document.querySelectorAll('.custom-control-label').forEach(label => {
                label.addEventListener('click', function () {
                    if (!el.classList.contains('disabled-nestable')) {
                        el.classList.add('disabled-nestable');
                    }
                });
            });

            jQuery(el).nestable({
                maxDepth: limit ? parseInt(limit, 10) : 999,
                expandBtnHTML: '',
                collapseBtnHTML: '',
                expandContentBtnHTML: '',
                collapseContentBtnHTML: '',
                callback: updateOutput
            });
        }

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
