export default function () {

    let websitesSelector = document.getElementById('websites-selector-form');

    if (websitesSelector) {

        let websiteId = websitesSelector.dataset.id;
        let option = websitesSelector.querySelector('option[value="' + websiteId + '"]');
        if (option) {
            option.selected = true;
        }

        websitesSelector.addEventListener('change', function (e) {
            if (e.target.tagName === 'SELECT') {
                websitesSelector.submit();
            }
        });
    }
}