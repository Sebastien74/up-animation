export default function () {

    if (document.querySelector('.nestable-list-container')) {
        import('../../../scss/admin/lib/nestable.scss');
    }

    const body = document.body;
    const isActive = body.classList.contains('editor');

    document.querySelectorAll('.nestable-list-container').forEach(function (el) {

        const elId = el.getAttribute('id');
        const outputFieldSelector = el.dataset.outputField;
        const limit = el.dataset.limit;

        /** Nestable */
        const updateOutput = function (e) {

            const windowLoadEl = body.querySelector('.nestable-window-load');
            const isFirstLoad = !windowLoadEl;
            const list = e instanceof HTMLElement ? e : e.target;
            const outputSelector = list.dataset.output;
            const output = document.querySelector(outputSelector);
            const form = el.querySelector('form.nestable-outpout-form');
            const formID = form ? form.getAttribute('id') : undefined;
            const preloader = body.querySelector('#nestable-list-preloader');

            if (output && formID && typeof jQuery !== 'undefined' && typeof jQuery.fn.nestable !== 'undefined') {

                output.value = JSON.stringify(jQuery(list).nestable('serialize'));

                const formData = new FormData(document.getElementById(formID));

                fetch(form.getAttribute('action'), {
                    method: 'POST',
                    body: formData,
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

                if (!isFirstLoad && preloader) {
                    preloader.classList.remove('d-none');
                }
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

            if (typeof jQuery !== 'undefined' && typeof jQuery.fn.nestable !== 'undefined') {

                const $nestableEl = jQuery(nestableEl);

                $nestableEl.nestable({
                    maxDepth: limit
                });

                $nestableEl.on('change', function () {
                    if (!nestableEl.classList.contains('disabled-nestable')) {
                        nestableEl.dataset.output = outputFieldSelector;
                        updateOutput(nestableEl);
                    }
                    nestableEl.classList.remove('disabled-nestable');
                });
            }
        }

        /** To use loader only if not the first load */
        el.insertAdjacentHTML('beforeend', '<span class="nestable-window-load"></span>');
    });

    let mouseY;
    const speed = 0.15;
    const zone = 50;

    document.addEventListener('mousemove', function (e) {
        mouseY = e.pageY - window.scrollY;
    });

    const dragInterval = setInterval(function () {
        const ddDragel = document.querySelector('.dd-dragel');

        if (ddDragel && !body.classList.contains('is-animated')) {

            const windowHeight = window.innerHeight;
            const bottom = windowHeight - zone;
            const scrollTop = window.scrollY;
            const documentHeight = document.documentElement.scrollHeight;

            if (mouseY > bottom && (scrollTop + windowHeight < documentHeight - zone)) {
                window.scrollTo({
                    top: scrollTop + ((mouseY + zone - windowHeight) * speed),
                    behavior: 'auto'
                });
            } else if (mouseY < zone && scrollTop > 0) {
                window.scrollTo({
                    top: scrollTop + ((mouseY - zone) * speed),
                    behavior: 'auto'
                });
            }
        }
    }, 16);

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