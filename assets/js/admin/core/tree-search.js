/**
 * Search in tree list
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    const searchInput = document.querySelector('.pages-search input');
    if (!searchInput) {
        return;
    }

    searchInput.addEventListener('keyup', function () {

        let term = this.value;
        const termLower = term.toLowerCase();
        const termRegex = term.replace(/(\s+)/, "(<[^>]+>)*$1(<[^>]+>)*");

        const nestableList = document.getElementById('nestable-list');
        if (!nestableList) {
            return;
        }

        const items = nestableList.querySelectorAll('.dd3-item');
        items.forEach(item => item.classList.remove('d-none'));

        const titles = nestableList.querySelectorAll('.dd3-content .title');

        const expandAllBtn = document.getElementById('nestable-expand-all');
        const collapseAllBtn = document.getElementById('nestable-collapse-all');
        if (expandAllBtn && !expandAllBtn.classList.contains('d-none') && term !== '') {
            expandAllBtn.click();
        } else if (collapseAllBtn && !collapseAllBtn.classList.contains('d-none') && term === '') {
            collapseAllBtn.click();
        }

        titles.forEach(title => {

            let srcStr = title.textContent;
            const pattern = new RegExp("(" + termRegex + ")", "gi");
            srcStr = srcStr.replace(pattern, "<mark class=\"bg-transparent\">$1</mark>");
            srcStr = srcStr.replace(/(<mark class="bg-transparent">[^<>]*)((<[^>]+>)+)([^<>]*<\/mark>)/, "$1</mark>$2<mark>$4");

            title.innerHTML = srcStr;

            if (term === '') {
                const marks = title.querySelectorAll('mark');
                marks.forEach(mark => {
                    const parent = mark.parentNode;
                    parent.replaceChild(document.createTextNode(mark.textContent), mark);
                    parent.normalize();
                });
            }

            const l = title.textContent.toLowerCase();

            if (term !== '' && l.indexOf(termLower) === -1) {
                title.classList.add('mark-muted');
            } else {
                title.classList.remove('mark-muted');
            }
        });

        let resultsCount = 0;
        if (term !== '') {
            items.forEach(item => item.classList.add('d-none'));
            titles.forEach(title => {
                const l = title.textContent.toLowerCase();
                if (l.indexOf(termLower) !== -1) {
                    resultsCount++;
                    let parentItem = title.closest('.dd3-item');
                    while (parentItem) {
                        parentItem.classList.remove('d-none');
                        parentItem = parentItem.parentElement.closest('.dd3-item');
                    }
                }
            });
        }

        if(resultsCount === 0) {
            document.getElementById('no-result-alert').classList.remove('d-none');
        } else {
            document.getElementById('no-result-alert').classList.add('d-none');
        }
    });
}