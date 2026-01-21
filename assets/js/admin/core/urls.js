/**
 * Urls status
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (event, el) {

    let iconEl = el.querySelectorAll('img')[0]
    let iconClass = iconEl.dataset.icon
    let bubble = el.querySelectorAll('.bubble')[0]
    let status = bubble.getAttribute('data-status')

    let beforeSend = function () {
        if (typeof iconClass != 'undefined') {
            iconEl.setAttribute('src', iconClass)
        }
        iconEl.classList.add('fa-spin')
    }

    let xHttp = new XMLHttpRequest()
    xHttp.open("GET", el.getAttribute('href'), true)
    xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8")
    beforeSend()
    xHttp.send()
    xHttp.onload = function (e) {

        if (this.readyState === 4 && this.status === 200) {

            let response = JSON.parse(this.response)
            let iconEl = el.querySelectorAll('img')[0]

            if (typeof iconClass != 'undefined') {
                iconEl.classList.remove("fa-spin")
                iconEl.setAttribute('src', iconClass)
                iconEl.setAttribute('data-icon', iconClass)
            } else {
                iconEl.classList.remove('fa-spin')
            }

            el.classList.remove(status)
            el.classList.add(response.status)
            bubble.classList.remove(status)
            bubble.classList.add(response.status)
            bubble.setAttribute('data-status', response.status)
        }
    }
    xHttp.onerror = function (errors) {
        // /** Display errors */
        // import('./errors').then(({default: displayErrors}) => {
        //     new displayErrors(errors);
        // }).catch(error => console.error(error.message));
    }

    event.stopImmediatePropagation()
    return false
}