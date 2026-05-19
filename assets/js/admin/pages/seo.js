import preview from './seo/preview';
import search from './seo/search';
import '../../vendor/plugins/prism';
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

    // Show expand-all when everything is collapsed, collapse-all otherwise.
    // Called after init (where active-link ancestors are auto-expanded), after
    // bulk clicks, and on every individual Bootstrap collapse toggle.
    function refreshTreeToggle(pane) {
        if (!pane) {
            return;
        }
        const hasOpen = pane.querySelector('.collapse.show') !== null;
        const expandBtn = pane.querySelector('.expand-all-entities');
        const collapseBtn = pane.querySelector('.collapse-all-entities');
        if (expandBtn) {
            expandBtn.classList.toggle('d-none', hasOpen);
        }
        if (collapseBtn) {
            collapseBtn.classList.toggle('d-none', !hasOpen);
        }
    }

    document.querySelectorAll('#v-pills-tab-tree .tab-pane').forEach(pane => {
        refreshTreeToggle(pane);
        pane.addEventListener('shown.bs.collapse', () => refreshTreeToggle(pane));
        pane.addEventListener('hidden.bs.collapse', () => refreshTreeToggle(pane));
    });

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
                refreshTreeToggle(pane);
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
                refreshTreeToggle(pane);
            }
        }
    });
});