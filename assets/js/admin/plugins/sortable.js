/**
 * Sortable
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    /** Select all in field */
    let sortable = $('.ui-sortable');
    if (sortable.length > 0) {
        $('.form-control').on('keydown', function (e) {
            let isSortable = $(this).closest('.ui-sortable').length > 0;
            if (e.keyCode == 65 && e.ctrlKey && isSortable) {
                e.target.select();
            }
        });
    }
};