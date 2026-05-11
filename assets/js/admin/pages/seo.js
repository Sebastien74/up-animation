import preview from './seo/preview';
import search from './seo/search';
import '../../../scss/admin/pages/seo.scss';
import '../../../scss/vendor/components/_prism.scss';

document.addEventListener('DOMContentLoaded', function () {
    search();

    const seoForm = document.querySelector('body form[name="seo"]');
    if (seoForm) {
        let previewTimer;
        seoForm.addEventListener('input', function () {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(preview, 200);
        });
    }

    const activeLink = document.querySelector('#v-pills-tab-tree .entities-list .link-item.active');
    if (activeLink) {
        let parent = activeLink.closest('.collapse');
        while (parent) {
            parent.classList.add('show');
            const toggle = document.querySelector(`[href="#${parent.id}"]`);
            if (toggle) {
                toggle.classList.remove('collapsed');
                toggle.setAttribute('aria-expanded', 'true');
            }
            parent = parent.parentElement.closest('.collapse');
        }
    }

    document.addEventListener('change', function (e) {
        const indexCheckbox = e.target.closest('.is-index');
        if (indexCheckbox) {
            const prism = document.getElementById('highlight-preview');
            if (prism) {
                const value = indexCheckbox.checked ? 'index' : 'noindex';
                const highlightIndex = prism.querySelector('.highlight-index');
                if (highlightIndex) {
                    highlightIndex.innerHTML = '&lt;meta name="robots" content="' + value + '" />';
                    if (window.Prism) {
                        Prism.highlightElement(highlightIndex);
                    }
                }
            }
        }
    });

    document.addEventListener('click', function (e) {
        const expandBtn = e.target.closest('.expand-all-entities');
        if (expandBtn) {
            const pane = expandBtn.closest('.tab-pane');
            if (pane) {
                pane.querySelectorAll('.collapse').forEach(el => el.classList.add('show'));
                pane.querySelectorAll('.collapse-icon').forEach(el => {
                    el.setAttribute('aria-expanded', 'true');
                    el.classList.remove('collapsed');
                });
            }
        }

        const collapseBtn = e.target.closest('.collapse-all-entities');
        if (collapseBtn) {
            const pane = collapseBtn.closest('.tab-pane');
            if (pane) {
                pane.querySelectorAll('.collapse').forEach(el => el.classList.remove('show'));
                pane.querySelectorAll('.collapse-icon').forEach(el => {
                    el.setAttribute('aria-expanded', 'false');
                    el.classList.add('collapsed');
                });
            }
        }
    });
});