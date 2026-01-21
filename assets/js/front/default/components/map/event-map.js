/**
 * Event map
 */
export default function (mapContainer) {

    mapHeight()

    let loader = mapContainer.getElementsByClassName('stripe-preloader')[0]
    let form = document.getElementById('events-filters-form')
    let formId = form.getAttribute('id')

    loader.classList.add('d-none')

    if (form) {
        let inputs = form.querySelectorAll('input')
        for (let i = 0; i < inputs.length; i++) {
            let input = inputs[i]
            let parent = input.closest('.form-check')
            input.addEventListener('change', (e) => {
                if (parent) {
                    parent.classList.toggle('active')
                }
                post()
            })
        }
    }

    let beforeSend = function (loader) {
        loader.classList.remove('d-none')
    }

    function post() {

        let uri = '?' + new URLSearchParams(Array.from(new FormData(form))).toString()
        if (uri) {
            history.pushState({}, null, uri);
        } else {
            let uri = window.location.toString();
            let cleanUri = uri.substring(0, uri.indexOf("?"));
            window.history.replaceState({}, document.title, cleanUri);
        }

        let xHttp = new XMLHttpRequest()
        xHttp.open("GET", form.getAttribute('action') + uri, true)
        beforeSend(loader)
        xHttp.send()
        xHttp.onload = function (e) {
            if (this.readyState === 4 && this.status === 200) {
                let response = JSON.parse(this.response)
                let html = document.createElement('div')
                html.innerHTML = response.html
                let form = html.getElementsByClassName('form-event-ajax')[0]
                let container = document.getElementById(formId).closest('.map-container').getElementsByClassName('map-filter-box')[0]
                container.innerHTML = form.closest('.map-container').getElementsByClassName('map-filter-box')[0].innerHTML
                let maps = document.querySelectorAll('.map-box')
                loader.classList.add('d-none')
                import('./map').then(({default: mapModule}) => {
                    new mapModule(maps)
                }).catch(error => console.error(error.message));
            }
        }
    }

    function mapHeight() {

        let points = mapContainer.getElementsByClassName('point')
        let minHeight = 500
        let mapHeight = 0

        for (let i = 0; i < points.length; i++) {
            let point = points[i]
            let height = point.offsetHeight
            if (height > mapHeight && height > minHeight) {
                mapHeight = height;
            }
        }
        if (mapHeight > minHeight) {
            document.getElementById("map-events").style.height = mapHeight + 'px';
        }
    }
}