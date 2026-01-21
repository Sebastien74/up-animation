/**
 * Dropdowns
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    import('../dist/dropdown').then(({default: Dropdown}) => {
        let dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
        let dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new Dropdown(dropdownToggleEl);
        });
    }).catch(error => console.error(error.message));
}