/**
 * Urls status
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import {Tooltip} from '../bootstrap-modules';

export default function (event, el) {

    const iconEl = el.querySelector('.label');
    const iconRefresh = el.querySelector('.refresh-icon');

    let beforeSend = function () {
        iconEl.classList.add('d-none');
        iconRefresh.classList.remove('d-none');
    }

    let xHttp = new XMLHttpRequest()
    xHttp.open("GET", el.getAttribute('href'), true)
    xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8")
    beforeSend()
    xHttp.send()
    xHttp.onload = function (e) {

        if (this.readyState === 4 && this.status === 200) {

            let response = JSON.parse(this.response);

            const iconEl = el.querySelector('.label');
            const iconRefresh = el.querySelector('.refresh-icon');

            const status = response.status;
            const iconOffline = el.dataset.iconOffline;
            const iconOnline = el.dataset.iconOnline;
            const colorOffline = el.dataset.colorOffline;
            const colorOnline = el.dataset.colorOnline;
            const labelOffline = el.dataset.labelOffline;
            const labelOnline = el.dataset.labelOnline;

            const iconPrevious = status === 'offline' ? iconOnline : iconOffline;
            const iconCurrent = status === 'offline' ? iconOffline : iconOnline;

            iconEl.classList.remove('icm-' + iconPrevious);
            iconEl.classList.add('icm-' + iconCurrent);

            const colorPrevious = status === 'offline' ? colorOnline : colorOffline;
            const colorCurrent = status === 'offline' ? colorOffline : colorOnline;
            const title = status === 'offline' ? labelOffline : labelOnline;

            el.classList.remove(colorPrevious);
            el.classList.add(colorCurrent);
            el.setAttribute('title', title);
            el.setAttribute('data-bs-original-title', title);
            el.setAttribute('aria-label', title);

            iconEl.classList.remove('d-none');
            iconRefresh.classList.add('d-none');
        }
    }
    xHttp.onerror = function (errors) {}

    event.stopImmediatePropagation()
    return false
}