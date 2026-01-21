/**
 * Toasts
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    import('../dist/toast').then(({default: Toast}) => {
        let els = [].slice.call(document.querySelectorAll('.toast'))
        let dropdownList = els.map(function (el) {
            return new Toast(el);
        });
    }).catch(error => console.error(error.message));
}