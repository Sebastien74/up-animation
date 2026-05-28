/**
 * Tooltips
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    import('../dist/tooltip').then(({default: Tooltip}) => {
        const hideAllExcept = (current) => {
            document.querySelectorAll('[data-bs-toggle="tooltip"].tooltip-loaded').forEach(el => {
                if (el === current) return;
                const instance = Tooltip.getInstance(el);
                if (instance) instance.hide();
            });
        };

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tooltip => {
            if (!tooltip.classList.contains('tooltip-loaded')) {
                let bsTooltip = new Tooltip(tooltip)
                tooltip.addEventListener('show.bs.tooltip', () => hideAllExcept(tooltip));
                tooltip.addEventListener('click', event => {
                    bsTooltip.update()
                    bsTooltip.hide()
                });
                tooltip.classList.add('tooltip-loaded');
            }
        });
    }).catch(error => console.error(error.message));
}