import serialize from "../../../vendor/components/serialize";
import route from "../../core/routing";
import Tooltip from '../../bootstrap/dist/tooltip';

/**
 * Set positions
 */
export default function (Routing, items, routeName, block = false) {

    let body = document.body;
    let loader = body.querySelector('#main-preloader');

    if (loader) {
        loader.classList.remove('d-none');
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        let tooltip = Tooltip.getInstance(el);
        if (tooltip) {
            tooltip.hide();
        }
    });

    let data = {};
    items.forEach(function (el, i) {
        let newPosition = i + 1;
        let elementId = el.getAttribute('id');
        let id = el.dataset.id;
        let element = document.getElementById(elementId);
        if (element) {
            element.setAttribute('data-position', newPosition);
        }
        if (block) {
            let column = el.closest('.column');
            data[id] = [column ? column.dataset.id : null, newPosition];
        } else {
            data[id] = newPosition;
        }
    });

    if (Object.keys(data).length > 0) {
        fetch(route(Routing, routeName, {website: body.dataset.id, data: serialize(data)}) + "?ajax=true", {
            method: 'POST'
        })
            .then(response => response.json())
            .then(() => {
                if (loader) {
                    loader.classList.add('d-none');
                }
            })
            .catch(errors => {
                /** Display errors */
                import('../../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            });
    }
}