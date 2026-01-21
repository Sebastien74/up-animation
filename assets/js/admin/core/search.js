/**
 * Search
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    $('.search-in-list input').keyup(function () {

        let el = $(this);
        let term = el.val();
        let targetAttr = el.data('target');
        let itemAttr = el.data('item');
        let target = el.closest('div[role="search"]').find(targetAttr);
        term = term.replace(/(\s+)/, "(<[^>]+>)*$1(<[^>]+>)*");

        target.find(itemAttr).each(function () {

            let item = $(this);
            let srcStr = item.text();

            let pattern = new RegExp("(" + term + ")", "gi");
            srcStr = srcStr.replace(pattern, "<mark class=\"bg-info\">$1</mark>");
            srcStr = srcStr.replace(/(<mark class="bg-info">[^<>]*)((<[^>]+>)+)([^<>]*<\/mark>)/, "$1</mark>$2<mark>$4");

            item.html(srcStr);

            if (term === '') {
                item.find('mark').remove();
            }

            let e = '(^| )' + term;
            let l = $(this).text();
            let a = new RegExp(e, "i");

            if (!a.test(l)) {
                item.addClass('text-muted');
            } else {
                item.removeClass('text-muted');
            }
        });
    });
}