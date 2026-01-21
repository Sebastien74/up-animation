/**
 * Popover
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import Popover from '../dist/popover'

let popovers = document.querySelectorAll('[data-bs-toggle="popover"]')

for (let i = 0; i < popovers.length; i++) {

    let popoverEl = popovers[i]
    let bsPopover = new Popover(popoverEl, {
        html: true
    })

    popoverEl.addEventListener('show.bs.popover', function () {

        let popoverEls = document.querySelectorAll('[data-bs-toggle="popover"]')
        for (let j = 0; j < popoverEls.length; j++) {
            let popoverElement = popoverEls[j]
            if (!popoverElement.isSameNode(popoverEl) && popoverElement.classList.contains('visible')) {
                popoverElement.classList.remove('visible')
                popoverElement.classList.add('not-visible')
            } else if (popoverElement.isSameNode(popoverEl)) {
                popoverElement.classList.remove('not-visible')
                popoverElement.classList.add('visible')
            }
        }

        let notVisibleEls = document.querySelectorAll('.not-visible[data-bs-toggle="popover"]')
        for (let j = 0; j < notVisibleEls.length; j++) {
            let popoverElement = notVisibleEls[j]
            let clickEvent = new CustomEvent('click')
            popoverElement.dispatchEvent(clickEvent)
            popoverElement.classList.remove('not-visible')
        }
    })

    popoverEl.addEventListener('shown.bs.popover', function () {
        let closeButtons = document.body.getElementsByClassName('close-popover')
        if (closeButtons) {
            for (let i = 0; i < closeButtons.length; i++) {
                let close = closeButtons[i]
                close.onclick = function (e) {
                    e.preventDefault()
                    let visibleEls = document.querySelectorAll('.visible[data-bs-toggle="popover"]')
                    for (let j = 0; j < visibleEls.length; j++) {
                        let popoverElement = visibleEls[j]
                        let clickEvent = new CustomEvent('click')
                        popoverElement.dispatchEvent(clickEvent)
                        popoverElement.classList.remove('visible')
                    }
                }
            }
        }
    })
}