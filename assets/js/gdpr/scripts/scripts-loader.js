import route from "../../vendor/components/routing"
import Cookies from 'js-cookie'

/**
 * Script loader
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (reloadActivation = false, saveIcon = false, reload = false, gdprData = null, run = false) {

    let cookieName = 'felixCookies'
    let FelixCookies = Cookies.get(cookieName)

    if (typeof FelixCookies !== 'undefined' && !run || run === true) {

        let xHttp = new XMLHttpRequest()
        xHttp.open("GET", route('front_gdpr_scripts', {_format: 'json', 'gdprData': gdprData}), true)
        xHttp.setRequestHeader("Content-Type", "application/json")
        xHttp.onload = function (e) {

            if (this.readyState === 4 && this.status === 200) {

                let cookieName = 'felixCookies'
                let response = JSON.parse(this.response)
                let head = document.head
                let body = document.body
                let html = document.documentElement
                let headerScripts = response.headerScripts
                let bodyPrependScripts = response.bodyPrependScripts
                let bodyAppendScripts = response.bodyAppendScripts
                let scripts = html.querySelectorAll('script')
                let preloader = document.getElementById('main-preloader')

                /** To reload script if cookie not set */
                if (!response.haveCookies || Cookies.get(cookieName) !== response.cookies) {
                    /** Scripts loader */
                    import('./scripts-loader').then(({default: scriptsLoader}) => {
                        new scriptsLoader(true, saveIcon, reload)
                    }).catch(error => console.error(error.message));
                    return false
                }

                if (typeof Cookies.get(cookieName) != 'undefined' && response.reloadModal) {
                    import('./modal').then(({default: modal}) => {
                        new modal(true)
                    }).catch(error => console.error(error.message));
                }

                let analyticsScripts = html.getElementsByClassName('analytics-script')
                for (let i = 0; i < analyticsScripts.length; i++) {
                    analyticsScripts[i].remove()
                }

                for (let i = 0; i < scripts.length; i++) {
                    let script = scripts[i]
                    let src = script.getAttribute('src')
                    let content = script.innerText
                    if (src && src.toLowerCase().indexOf('google-analytics') >= 0) {
                        script.remove()
                    }
                    if (src && src.toLowerCase().indexOf('googletagmanager') >= 0
                        || content && content.toLowerCase().indexOf('googletagmanager')) {
                        script.remove()
                    }
                }

                if (headerScripts.trim()) {
                    /** Create object html */
                    let prependNode = document.createElement('div')
                    prependNode.innerHTML = headerScripts.trim()
                    let scriptsToInject = prependNode.getElementsByTagName('script')
                    for (let i = 0; i < scriptsToInject.length; i++) {
                        /** Create script elem */
                        let scriptToInject = scriptsToInject[i]
                        let scriptHead = document.createElement("script")
                        scriptHead.type = "text/javascript"
                        scriptHead.text = scriptToInject.textContent
                        /** Inject */
                        head.prepend(scriptHead)
                    }
                }

                if (bodyAppendScripts.trim()) {
                    /** Create object html */
                    let prependNode = document.createElement('div')
                    prependNode.innerHTML = bodyAppendScripts.trim()
                    let scriptsToInject = prependNode.getElementsByTagName('script')
                    for (let i = 0; i < scriptsToInject.length; i++) {
                        /** Create script elem */
                        let scriptToInject = scriptsToInject[i]
                        let scriptBodyAppend = document.createElement("script")
                        scriptBodyAppend.type = "text/javascript"
                        scriptBodyAppend.text = scriptToInject.textContent
                        /** Inject */
                        body.appendChild(scriptBodyAppend)
                    }
                }

                if (bodyPrependScripts.trim()) {
                    let prependNode = document.createElement('div')
                    prependNode.innerHTML = bodyPrependScripts.trim()
                    body.prepend(prependNode.children[0])
                }

                let saveIconEl = saveIcon ? document.getElementById(saveIcon.getAttribute('id')) : ''
                if (saveIcon && saveIconEl) {
                    if (preloader) {
                        preloader.classList.remove('d-none')
                        preloader.classList.remove('disappear')
                    }
                    location.reload()
                }
            }
        }
        xHttp.send()
    }
}