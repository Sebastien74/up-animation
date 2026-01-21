/**
 * Tooltips
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    import('../dist/tooltip').then(({default: Tooltip}) => {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tooltip => {
            if (!tooltip.classList.contains('tooltip-loaded')) {
                let bsTooltip = new Tooltip(tooltip)
                tooltip.addEventListener('click', event => {
                    bsTooltip.update()
                    bsTooltip.hide()
                });
                tooltip.classList.add('tooltip-loaded');
            }
        });
    }).catch(error => console.error(error.message));
}