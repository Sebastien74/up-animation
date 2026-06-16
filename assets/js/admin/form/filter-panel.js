/**
 * Index filters dropdown panel.
 *
 * Each row has an enable checkbox: when off, the row controls are disabled so they are
 * not submitted (and the filter is ignored server-side); typing/selecting a value
 * auto-enables the row. Rows pre-filled by the server start enabled.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default class FilterPanel {
    constructor() {
        document.querySelectorAll('.filters-form').forEach(form => this.bindForm(form));
    }

    bindForm(form) {
        form.querySelectorAll('[data-filter-row]').forEach(row => {
            const toggle = row.querySelector('.filters-row-toggle');
            const controls = row.querySelectorAll('.filters-row-control input, .filters-row-control select, .filters-row-control textarea');
            if (!toggle || !controls.length) {
                return;
            }

            const sync = () => controls.forEach(control => {
                control.disabled = !toggle.checked;
            });

            sync();
            toggle.addEventListener('change', sync);

            controls.forEach(control => {
                const enable = () => {
                    if (!toggle.checked) {
                        toggle.checked = true;
                        sync();
                    }
                };
                control.addEventListener('input', enable);
                control.addEventListener('change', enable);
            });
        });
    }
}
