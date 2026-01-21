/**
 * Plugins
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 *  1 - Select2
 *  2 - Autocomplete
 */
export default function () {

    let body = document.body

    /** 1 - Select2 */

    let selects = body.querySelectorAll('.select-2')
    let selectsIcons = body.querySelectorAll('.select-icons')

    if (selects.length > 0 || selectsIcons.length > 0) {
        import('./select2').then(({default: select2}) => {
            new select2();
        }).catch(error => console.error(error.message));
    }

    /** 2 - Autocomplete */
    let autocompletes = body.querySelectorAll('.js-autocomplete')
    if (autocompletes.length > 0) {
        import('./autocomplete').then(({default: autocomplete}) => {
            new autocomplete()
        }).catch(error => console.error(error.message));
    }
};