import removeErrors from "../../vendor/components/remove-errors";
import '../../../scss/admin/pages/menu.scss';

document.addEventListener('click', function (e) {

    const saveButton = e.target.closest('#link_save');
    if (saveButton) {
        e.preventDefault();

        const loader = document.querySelector('.main-preloader');
        const form = saveButton.closest('form');
        const url = form.getAttribute('action') + (form.getAttribute('action').includes('?') ? '&' : '?') + 'ajax=true';
        const formData = new FormData(form);

        removeErrors();
        if (loader) {
            loader.classList.remove('d-none');
        }

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(response => {
            if (response.html && !response.success) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = response.html;
                const newContent = tempDiv.querySelector("#link-form-content");
                let currentContent = form.querySelector("#link-form-content");

                if (!currentContent) {
                    currentContent = form.closest("#link-form-content");
                }

                if (currentContent && newContent) {
                    currentContent.replaceWith(newContent);
                }
                
                if (loader) {
                    loader.classList.add('d-none');
                }
            }

            if (response.success) {
                window.location.reload();
            }
        })
        .catch(errors => {
            import('../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
            if (loader) {
                loader.classList.add('d-none');
            }
        });

        e.stopImmediatePropagation();
        return false;
    }

    const expandAllBtn = e.target.closest('.expand-all-pages');
    if (expandAllBtn) {
        const list = document.getElementById('pages-list');
        if (list) {
            list.querySelectorAll('.collapse').forEach(el => el.classList.add('show'));
            list.querySelectorAll('.collapse-icon').forEach(el => {
                el.setAttribute('aria-expanded', 'true');
                el.classList.remove('collapsed');
            });

            expandAllBtn.classList.add('d-none');
            const collapseAllBtn = expandAllBtn.parentElement.querySelector('.collapse-all-pages');
            if (collapseAllBtn) {
                collapseAllBtn.classList.remove('d-none');
            }
        }
    }

    const collapseAllBtn = e.target.closest('.collapse-all-pages');
    if (collapseAllBtn) {
        const list = document.getElementById('pages-list');
        if (list) {
            list.querySelectorAll('.collapse').forEach(el => el.classList.remove('show'));
            list.querySelectorAll('.collapse-icon').forEach(el => {
                el.setAttribute('aria-expanded', 'false');
                el.classList.add('collapsed');
            });

            collapseAllBtn.classList.add('d-none');
            const expandAllBtn = collapseAllBtn.parentElement.querySelector('.expand-all-pages');
            if (expandAllBtn) {
                expandAllBtn.classList.remove('d-none');
            }
        }
    }
});