import scrollToEl from './scroll-to'

/**
 * Scroll to errors
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let scroll = false
    let errors = document.getElementsByClassName('invalid-feedback')

    if (!scroll && errors.length > 0) {

        let el = errors[0]
        let isCollapse = el.closest('.collapse')
        let isTab = el.closest('.tab-pane')

        if (isCollapse) {
            let collapseId = isCollapse.getAttribute('id')
            document.querySelectorAll("*[data-target='#" + collapseId + "']")[0].click()
        }

        if (isTab) {

            let target = isTab.getAttribute('aria-labelledby')
            document.getElementById(target).click()
            el = document.getElementById(isTab.getAttribute('id')).getElementsByClassName('invalid-feedback')[0]

            setTimeout(function () {
                scrollToEl(el)
                scroll = true
            }, 200)

        } else {
            scrollToEl(el)
            scroll = true
        }
    }
}