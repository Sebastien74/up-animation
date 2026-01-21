import rewriteUrl from "./rewrite-url";
import resetCounter from "./reset-counter";
import loadItems from "./load-items";

export default function(body, uri, response, loader, term = null) {

    let items = $(response.html).find(".item");

    rewriteUrl(uri);
    resetCounter(body, items.length);
    loadItems(body, items, loader, term);
}