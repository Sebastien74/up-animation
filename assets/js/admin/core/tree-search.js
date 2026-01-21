/**
 * Search in tree list
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    $('.pages-search input').keyup(function () {

        let term = this.value;
        term = term.replace(/(\s+)/, "(<[^>]+>)*$1(<[^>]+>)*");

        $('#nestable-list').find('.dd3-content .title').each(function () {

            let body = $('body');
            let expandBtn = body.find('.expand-btn');
            if (!expandBtn.hasClass('active')) {
                expandBtn.addClass('active');
                expandBtn.trigger('click')
            }

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