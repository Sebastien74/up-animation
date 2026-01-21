import Utils from 'fullib-js/src/js/Utils/Utils';

export default function () {

    let utils = new Utils();
    let searchBox = false;
    let formSearch = document.querySelector('.search-engine-form');
    let searchInput = document.querySelector('.search-engine-form #search');
    let autoComplete = document.querySelector('.search-engine-form .autocomplete');
    let timeout = null;

    if (autoComplete && searchInput) {
        if (!autoComplete.classList.contains('loaded')) {

            autoComplete.classList.add('loaded');
            if (searchInput.value !== '') {
                request(searchInput.value);
            }

            searchInput.addEventListener('input', () => {
                autoComplete.setAttribute('data-text', searchInput.value);
                if (searchInput.value === '' && searchBox) {
                    searchBox.remove();
                }
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    request(searchInput.value);
                }, 1000);
            });

            // Ferme le searchBox si on clique en dehors
            document.addEventListener('click', (event) => {
                if (searchBox && !searchBox.contains(event.target) && event.target !== searchInput) {
                    searchBox.classList.add('close');
                }
            });

            //OUVRE SI ON CLIQUE SUR LE INPUT
            searchInput.addEventListener('click', () => {
                if (searchBox) {
                    searchBox.classList.remove('close');
                }
            });
        }
    }

    function getHtml(completion) {
        let html = false;
        if (Object.keys(completion).length > 0) {
            html = `<div class="items">`;
            Object.keys(completion).forEach(category => {
                html += `<div class="classname">${category}</div>`;
                Object.values(completion[category]).forEach(item => {
                    html += `<div class="item" data-type="${item.type}">${item.label}</div>`;
                });
            });
            html += `</div>`;
        }
        return html;
    }

    function request(userKeyword) {
        let xHttp = new XMLHttpRequest();
        let ajaxUrl = autoComplete.getAttribute('data-ajax') + '&userkeyword=' + encodeURIComponent(userKeyword);
        xHttp.open("GET", ajaxUrl, true);
        xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
        xHttp.send();
        xHttp.onload = function () {
            if (this.readyState === 4 && this.status === 200) {
                let response = JSON.parse(this.response);
                let completion = response.response.completion;
                if (searchBox) {
                    searchBox.remove();
                }
                let htmlContent = getHtml(completion);
                if (htmlContent) {
                    searchBox = utils.addElement('div', '', {id: 'search-box', addTo: autoComplete, text: htmlContent});
                    let items = searchBox.querySelectorAll('.item');
                    items.forEach(item => {
                        item.addEventListener('click', () => {
                            searchBox.classList.add('close');
                            searchInput.value = item.textContent;
                            formSearch.submit();
                        });
                    });
                }
            }
        };
    }
}