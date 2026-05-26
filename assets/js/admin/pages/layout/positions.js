import serialize from "../../../vendor/components/serialize";
import route from "../../core/routing";
import Tooltip from '../../bootstrap/dist/tooltip';

// Module-scoped counter so concurrent drags share state. Without this,
// AJAX 1 completing after drag 2 started would hide the loader the
// instant drag 2 begins — second drag visually skips the loader.
let inFlight = 0;

function acquire(loader) {
    inFlight += 1;
    if (loader) {
        loader.classList.remove('d-none');
    }
}

function release(loader) {
    inFlight = Math.max(0, inFlight - 1);
    if (inFlight === 0 && loader) {
        loader.classList.add('d-none');
    }
}

/**
 * Set positions
 */
export default function (Routing, items, routeName, block = false) {

    const body = document.body;
    const loader = body.querySelector('#main-preloader');

    acquire(loader);

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        const tooltip = Tooltip.getInstance(el);
        if (tooltip) {
            tooltip.hide();
        }
    });

    const data = {};
    items.forEach(function (el, i) {
        const newPosition = i + 1;
        const elementId = el.getAttribute('id');
        const id = el.dataset.id;
        const element = document.getElementById(elementId);
        if (element) {
            element.setAttribute('data-position', newPosition);
        }
        if (block) {
            const column = el.closest('.column');
            data[id] = [column ? column.dataset.id : null, newPosition];
        } else {
            data[id] = newPosition;
        }
    });

    const rebindLayout = () => import('./vendor').then(({default: layoutActivation}) => {
        layoutActivation(Routing);
    }).catch(error => console.error(error.message));

    if (Object.keys(data).length === 0) {
        rebindLayout();
        release(loader);
        return;
    }

    fetch(route(Routing, routeName, {website: body.dataset.id, data: serialize(data)}) + "?ajax=true", {
        method: 'POST'
    })
        .then(response => response.json())
        .then(() => {
            rebindLayout();
            release(loader);
        })
        .catch(errors => {
            release(loader);
            /** Display errors */
            import('../../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
        });
}