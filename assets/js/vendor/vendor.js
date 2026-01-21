/**
 * Vendor
 *
 * @copyright 2020
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @version 1.0
 * @licence under the MIT License (LICENSE.txt)
 *
 *  1 - Components
 *  2 - Plugins
 *  3 - Users Switcher
 *  4 - Grabbable elements events
 *  5 - Log errors (Is comment)
 */

/** 1 - Components */
import './components/vendor'

/** 2 - Plugins */
import './plugins/vendor'

/** 3 - Users Switcher */
import switcher from "../security/switcher"
switcher()

/** 4 - Grabbable elements events */

const grabEls = document.querySelectorAll('.grabbable')

for (let i = 0; i < grabEls.length; i++) {
    let grabEl = grabEls[i]
    grabEl.addEventListener("mousedown", function (e) {
        grabEl.classList.add('active')
    })
    grabEl.addEventListener("mouseup", function (e) {
        grabEl.classList.remove('active')
    })
}

/** 5 - Log errors */
// import logErrors from "./core/log-errors";
// logErrors();