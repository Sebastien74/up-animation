/**
 * Tooltips
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    import('../bootstrap/dist/tooltip').then(({default: Tooltip}) => {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            const tooltip = new Tooltip(tooltipTriggerEl);
            tooltipTriggerEl.addEventListener('click', function () {
                tooltip.hide();
            });
            return tooltip;
        });
    }).catch(error => console.error(error.message));
};