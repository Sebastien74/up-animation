import masonry from "../../../../../../../../../assets/js/vendor/plugins/masonry";

export default function(body, items, loader, val = null) {

    let term = val ? val.replace(/(\s+)/, "(<[^>]+>)*$1(<[^>]+>)*") : null;
    let highlight = body.find('#scroll-wrapper').data('highlight');
    let blockResult = body.find('#results');

    blockResult.html('');
    items.each(function() {
        if(highlight && term) {
            let texts = $(this).find('.highlight');
            texts.each(function(i, el) {
                let item = $(el);
                let srcStr = item.text();
                let pattern = new RegExp("(" + term + ")", "gi");
                srcStr = srcStr.replace(pattern, "<mark class=\"bg-info\">$1</mark>");
                srcStr = srcStr.replace(/(<mark class="bg-info">[^<>]*)((<[^>]+>)+)([^<>]*<\/mark>)/, "$1</mark>$2<mark>$4");
                item.html(srcStr);
                if(term === '') {
                    item.find('mark').remove();
                }
            });
        }
        masonry($(this));
    });

    masonry($(this));

    loader.addClass('d-none');
}