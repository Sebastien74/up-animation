/**
 * Tooltips
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    let tooltips = $('[data-bs-toggle="tooltip"]');
    tooltips.tooltip();
    tooltips.click(function () {
        tooltips.tooltip("hide");
    });
};