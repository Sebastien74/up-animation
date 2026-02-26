import '../lib/select2totree';

/**
 * Tree select
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let treeSelects = document.querySelectorAll('.tree-select');

    if (treeSelects.length > 0 && typeof jQuery !== 'undefined' && typeof jQuery.fn.select2ToTree !== 'undefined') {
        jQuery(treeSelects).select2ToTree();
    }
}