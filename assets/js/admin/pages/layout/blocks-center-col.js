import route from "../../core/routing";

/**
 * Standardize Block[] width in Col
 */
export default function (Routing) {

    document.body.addEventListener('click', function handler(e) {

        let el = e.target.closest('.col-blocks-standardize');
        if (!el) return;

        e.preventDefault();

        let body = document.body;
        let website = body.dataset.id;
        let titleBlock = el.parentElement;
        let iconWraps = el.querySelectorAll('.icon-wrap');
        let col = el.getAttribute('data-col');
        let newStandardize = el.getAttribute('data-standardize') === 'true' ? 0 : 1;
        let standardize = newStandardize === 1 ? 'true' : 'false';
        let loader = body.querySelector('#main-preloader');

        if (loader) {
            loader.classList.toggle('d-none');
        }
        el.setAttribute('data-standardize', standardize);

        fetch(route(Routing, 'admin_blocks_standardize', {website: website, col: col, standardize: newStandardize}))
            .then(response => response.json())
            .then(() => {
                if (standardize === 'false') {
                    titleBlock.setAttribute('data-original-title', el.dataset.colsStandardize);
                    let tooltipInner = titleBlock.parentElement.querySelector('.tooltip-inner');
                    if (tooltipInner) {
                        tooltipInner.innerHTML = el.dataset.colsDefault;
                    }
                } else {
                    titleBlock.setAttribute('data-original-title', el.dataset.colsDefault);
                    let tooltipInner = titleBlock.parentElement.querySelector('.tooltip-inner');
                    if (tooltipInner) {
                        tooltipInner.innerHTML = el.dataset.colsStandardize;
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