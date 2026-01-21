/**
 * Catalog map.
 */
export default function (maps) {
    maps.forEach(function (map) {
        let mapContainer = map.closest('.map-container');
        let xHttp = new XMLHttpRequest();
        xHttp.open("GET", map.dataset.ajaxPath, true);
        xHttp.send();
        xHttp.onload = function () {
            if (this.readyState === 4 && this.status === 200) {
                let response = this.response;
                response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
                response = JSON.parse(response);
                let htmlEl = document.createElement('div');
                htmlEl.innerHTML = response.html;
                let loader = htmlEl.querySelector('.loader');
                if (loader) {
                    loader.classList.add('d-none');
                }
                let ajaxContainer = htmlEl.querySelector('.map-container');
                mapContainer.innerHTML = ajaxContainer.innerHTML;
                let map = mapContainer.querySelectorAll('.map-box-catalog');
                import('./map').then(({default: mapModule}) => {
                    new mapModule(map);
                }).catch(error => console.error(error.message));
            }
        }
    });
}