/**
 * Tooltips — single source of truth.
 *
 * Initializes Bootstrap tooltips with `trigger: hover` only and
 * force-hides them on mouseleave / click / drag so a tooltip never
 * lingers outside the cursor zone. Safe to call multiple times :
 * disposes the previous instance before recreating, so AJAX response
 * handlers can re-call it after replacing DOM.
 *
 * @param {Element|Document} [root=document]  Restrict init to a subtree.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (root = document) {

    return import('../bootstrap/dist/tooltip').then(({default: Tooltip}) => {

        function forceHide(trigger, tooltip) {
            tooltip.hide();
            const describedBy = trigger.getAttribute('aria-describedby');
            if (describedBy) {
                const stale = document.getElementById(describedBy);
                if (stale) stale.remove();
                trigger.removeAttribute('aria-describedby');
            }
        }

        // Bootstrap-style trigger attribute + legacy alias
        const selector = '[data-bs-toggle="tooltip"], [data-toggle="tooltip"]';
        const triggers = root.querySelectorAll(selector);

        triggers.forEach(function (triggerEl) {

            const existing = Tooltip.getInstance(triggerEl);
            if (existing) {
                existing.dispose();
            }

            const tooltip = new Tooltip(triggerEl, {
                trigger: 'hover',
            });

            triggerEl.addEventListener('mouseleave', function () {
                forceHide(triggerEl, tooltip);
            });
            triggerEl.addEventListener('click', function () {
                forceHide(triggerEl, tooltip);
            });
            triggerEl.addEventListener('blur', function () {
                forceHide(triggerEl, tooltip);
            });
        });
    }).catch(error => console.error(error.message));
};
