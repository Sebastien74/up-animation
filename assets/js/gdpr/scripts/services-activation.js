/**
 * Services activation
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (service, code, active) {
    let wraps = document.getElementsByClassName('gdpr-' + service + '-wrap')
    for (let i = 0; i < wraps.length; i++) {
        let el = wraps[i]
        let wrapCode = el.dataset.code
        if (wrapCode === code && active) {
            el.innerHTML = el.dataset.prototype
        } else if (wrapCode === code && !active) {
            el.innerHTML = el.dataset.prototypePlaceholder
        }
    }
}