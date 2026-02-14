/**
 * Search in tree list
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    $('.pages-search input').keyup(function () {

        let term = this.value;
        let termLower = term.toLowerCase();
        term = term.replace(/(\s+)/, "(<[^>]+>)*$1(<[^>]+>)*");

        let items = $('#nestable-list').find('.dd3-item');
        items.removeClass('d-none');

        $('#nestable-list').find('.dd3-content .title').each(function () {

            let body = $('body');
            let expandBtn = body.find('.expand-btn');
            if (!expandBtn.hasClass('active') && term !== '') {
                expandBtn.addClass('active');
                expandBtn.trigger('click')
            }

            let item = $(this);
            let srcStr = item.text();

            let pattern = new RegExp("(" + term + ")", "gi");
            srcStr = srcStr.replace(pattern, "<mark class=\"bg-transparent\">$1</mark>");
            srcStr = srcStr.replace(/(<mark class="bg-transparent">[^<>]*)((<[^>]+>)+)([^<>]*<\/mark>)/, "$1</mark>$2<mark>$4");

            item.html(srcStr);

            if (term === '') {
                item.find('mark').remove();
            }

            let l = $(this).text().toLowerCase();

            if (term !== '' && l.indexOf(termLower) === -1) {
                item.addClass('text-muted');
            } else {
                item.removeClass('text-muted');
            }
        });

        if (term !== '') {
            items.addClass('d-none');
            $('#nestable-list').find('.dd3-content .title').each(function () {
                let l = $(this).text().toLowerCase();
                if (l.indexOf(termLower) !== -1) {
                    $(this).closest('.dd3-item').removeClass('d-none').parents('.dd3-item').removeClass('d-none');
                }
            });
        }
    });
}