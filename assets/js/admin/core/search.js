/**
 * Search
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    document.querySelectorAll('.search-in-list input').forEach(input => {
        input.addEventListener('keyup', function () {

            let term = this.value;
            let targetSelector = this.dataset.target;
            let itemSelector = this.dataset.item;
            let searchWrapper = this.closest('div[role="search"]');
            let target = searchWrapper ? searchWrapper.querySelector(targetSelector) : null;

            if (!target) return;

            const termRegex = term.replace(/(\s+)/, "(<[^>]+>)*$1(<[^>]+>)*");

            target.querySelectorAll(itemSelector).forEach(item => {

                let srcStr = item.textContent;

                let pattern = new RegExp("(" + termRegex + ")", "gi");
                srcStr = srcStr.replace(pattern, "<mark class=\"bg-transparent\">$1</mark>");
                srcStr = srcStr.replace(/(<mark class="bg-transparent">[^<>]*)((<[^>]+>)+)([^<>]*<\/mark>)/, "$1</mark>$2<mark>$4");

                item.innerHTML = srcStr;

                if (term === '') {
                    item.querySelectorAll('mark').forEach(mark => {
                        const text = document.createTextNode(mark.textContent);
                        mark.replaceWith(text);
                    });
                }

                let e = '(^| )' + term;
                let l = item.textContent;
                let a = new RegExp(e, "i");

                if (!a.test(l)) {
                    item.classList.add('mark-muted');
                } else {
                    item.classList.remove('mark-muted');
                }
            });
        });
    });
}