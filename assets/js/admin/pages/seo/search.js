/**
 * Search
 */
export default function () {

    $('.seo-search input').keyup(function () {

        let el = $(this);
        let term = el.val();
        let target = el.closest('.tab-pane').find(el.data('target'));
        term = term.replace(/(\s+)/, "(<[^>]+>)*$1(<[^>]+>)*");

        if (term === '') {
            $('ul.nested').removeClass('active');
            $('ul.nested .item').removeClass('active');
        } else {
            $('ul.nested').addClass('active');
            $('ul.nested .item').addClass('active');
        }

        target.find('.link-item').each(function () {

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