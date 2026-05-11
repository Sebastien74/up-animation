/**
 * Tooltips
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    import('../bootstrap/dist/tooltip').then(({default: Tooltip}) => {

        function forceHide(trigger, tooltip) {
            tooltip.hide();
            const describedBy = trigger.getAttribute('aria-describedby');
            if (describedBy) {
                const stale = document.getElementById(describedBy);
                if (stale) stale.remove();
                trigger.removeAttribute('aria-describedby');
            }
        }

        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            const tooltip = new Tooltip(tooltipTriggerEl, {
                trigger: 'hover'
            });
            tooltipTriggerEl.addEventListener('mouseleave', function () {
                forceHide(tooltipTriggerEl, tooltip);
            });
            tooltipTriggerEl.addEventListener('click', function () {
                forceHide(tooltipTriggerEl, tooltip);
            });
            return tooltip;
        });
    }).catch(error => console.error(error.message));
};