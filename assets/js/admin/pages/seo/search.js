/**
 * Search
 */
export default function () {

    const seoSearchInputs = document.querySelectorAll('.seo-search input');
    seoSearchInputs.forEach(input => {
        input.addEventListener('keyup', function () {

            let term = input.value;
            let targetPane = input.closest('.tab-pane');
            let targetSelector = input.dataset.target;
            let target = targetPane ? targetPane.querySelector(targetSelector) : null;
            if (!target) return;

            term = term.replace(/(\s+)/, "(<[^>]+>)*$1(<[^>]+>)*");

            const nestedUls = document.querySelectorAll('ul.nested');
            const nestedItems = document.querySelectorAll('ul.nested .item');

            if (term === '') {
                nestedUls.forEach(ul => ul.classList.remove('active'));
                nestedItems.forEach(item => item.classList.remove('active'));
            } else {
                nestedUls.forEach(ul => ul.classList.add('active'));
                nestedItems.forEach(item => item.classList.add('active'));
            }

            target.querySelectorAll('.link-item').forEach(function (item) {

                let srcStr = item.textContent;

                let pattern = new RegExp("(" + term + ")", "gi");
                srcStr = srcStr.replace(pattern, "<mark class=\"bg-transparent\">$1</mark>");
                srcStr = srcStr.replace(/(<mark class="bg-transparent">[^<>]*)((<[^>]+>)+)([^<>]*<\/mark>)/, "$1</mark>$2<mark>$4");

                item.innerHTML = srcStr;

                if (term === '') {
                    item.querySelectorAll('mark').forEach(mark => mark.remove());
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