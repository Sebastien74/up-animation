/**
 * Sortable
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    /** Select all in field */
    let sortables = document.querySelectorAll('.ui-sortable');
    if (sortables.length > 0) {
        document.body.addEventListener('keydown', function (e) {
            let target = e.target;
            if (target && target.classList.contains('form-control')) {
                let isSortable = target.closest('.ui-sortable') !== null;
                if ((e.keyCode === 65 || e.key === 'a') && (e.ctrlKey || e.metaKey) && isSortable) {
                    target.select();
                }
            }
        });
    }
};