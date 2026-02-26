/**
 * Urls status
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import {Tooltip} from '../bootstrap-modules';

export default function (event, el) {

    const iconEl = el.querySelector('.label');
    const iconRefresh = el.querySelector('.refresh-icon');

    const beforeSend = function () {
        if (iconEl) iconEl.classList.add('d-none');
        if (iconRefresh) iconRefresh.classList.remove('d-none');
    }

    beforeSend();

    fetch(el.getAttribute('href'), {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json; charset=utf-8',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (response.ok) {
                return response.json();
            }
            throw new Error('Network response was not ok.');
        })
        .then(response => {
            const status = response.status;
            const iconOffline = el.dataset.iconOffline;
            const iconOnline = el.dataset.iconOnline;
            const colorOffline = el.dataset.colorOffline;
            const colorOnline = el.dataset.colorOnline;
            const labelOffline = el.dataset.labelOffline;
            const labelOnline = el.dataset.labelOnline;

            const iconPrevious = status === 'offline' ? iconOnline : iconOffline;
            const iconCurrent = status === 'offline' ? iconOffline : iconOnline;

            if (iconEl) {
                iconEl.classList.remove('icm-' + iconPrevious);
                iconEl.classList.add('icm-' + iconCurrent);
            }

            const colorPrevious = status === 'offline' ? colorOnline : colorOffline;
            const colorCurrent = status === 'offline' ? colorOffline : colorOnline;
            const title = status === 'offline' ? labelOffline : labelOnline;

            el.classList.remove(colorPrevious);
            el.classList.add(colorCurrent);
            el.setAttribute('title', title);
            el.setAttribute('data-bs-original-title', title);
            el.setAttribute('aria-label', title);

            if (iconEl) iconEl.classList.remove('d-none');
            if (iconRefresh) iconRefresh.classList.add('d-none');
        })
        .catch(error => {
            console.error('Error:', error);
            if (iconEl) iconEl.classList.remove('d-none');
            if (iconRefresh) iconRefresh.classList.add('d-none');
        });

    event.stopImmediatePropagation();
    return false;
}