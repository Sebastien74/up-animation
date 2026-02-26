import route from "../../core/routing";

/**
 * Zones resize
 */
export default function (Routing) {

    document.body.addEventListener('click', function handler(e) {

        let el = e.target.closest('.zone-resize');
        if (!el) return;

        e.preventDefault();

        let body = document.body;
        let website = body.dataset.id;
        let titleBlock = el.parentElement;
        let iconWraps = el.querySelectorAll('.icon-wrap');
        let zone = el.getAttribute('data-zone');
        let newSize = el.getAttribute('data-size') === 'true' ? 0 : 1;
        let size = newSize === 1 ? 'true' : 'false';
        let loader = body.querySelector('#layout-preloader');

        if (loader) {
            loader.classList.toggle('d-none');
        }
        el.setAttribute('data-size', size);

        fetch(route(Routing, 'admin_zone_size', {website: website, zone: zone, size: newSize}) + "&ajax=true")
            .then(response => response.json())
            .then(() => {
                if (size === 'false') {
                    titleBlock.setAttribute('data-original-title', el.dataset.compress);
                    let tooltipInner = titleBlock.parentElement.querySelector('.tooltip-inner');
                    if (tooltipInner) {
                        tooltipInner.innerHTML = el.dataset.compress;
                    }
                } else {
                    titleBlock.setAttribute('data-original-title', el.dataset.expand);
                    let tooltipInner = titleBlock.parentElement.querySelector('.tooltip-inner');
                    if (tooltipInner) {
                        tooltipInner.innerHTML = el.dataset.expand;
                    }
                }
                iconWraps.forEach(iconWrap => iconWrap.classList.toggle('d-none'));
                if (loader) {
                    loader.classList.toggle('d-none');
                }
            })
            .catch(errors => {
                /** Display errors */
                import('../../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            });

        e.stopPropagation();
        return false;
    });
}