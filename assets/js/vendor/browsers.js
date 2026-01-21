/**
 * Browsers
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 *  1 - IE Browsers Alert
 *  2 - Add browser class on body
 */

let body = document.body

let browsers = {
    isAndroid: /Android/.test(navigator.userAgent),
    isCordova: !!window.cordova,
    isEdge: /Edge/.test(navigator.userAgent),
    isFirefox: /Firefox/.test(navigator.userAgent),
    isChrome: /Google Inc/.test(navigator.vendor),
    isChromeIOS: /CriOS/.test(navigator.userAgent),
    isChromiumBased: !!window.chrome && !/Edge/.test(navigator.userAgent),
    isIE: /Trident/.test(navigator.userAgent),
    isIOS: /(iPhone|iPad|iPod)/.test(navigator.platform),
    isOpera: /OPR/.test(navigator.userAgent),
    isSafari: /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent),
    isTouchScreen: ('ontouchstart' in window) || window.DocumentTouch && document instanceof DocumentTouch,
    isWebComponentsSupported: 'registerElement' in document && 'import' in document.createElement('link') && 'content' in document.createElement('template')
}

/** 1 - IE Browsers Alert */
if (browsers.isIE) {

    let preloader = document.getElementById('main-preloader')
    if(preloader) {
        preloader.parentNode.removeChild(preloader)
    }

    body.classList.add('deactivate-gdpr')

    let xHttp = new XMLHttpRequest()
    xHttp.open("GET", '/core/browser/ie/alert', true)
    xHttp.setRequestHeader("Content-Type", "application/json")
    xHttp.onload = function (e) {
        if (this.readyState === 4 && this.status === 200) {
            let response = JSON.parse(this.response)
            if (response.html) {
                let alert = document.createElement('div')
                alert.innerHTML = response.html
                body.appendChild(alert)
            }
        }
    }
    xHttp.send()
}

/** 2 - Add browser class on body */
for (const [browser, status] of Object.entries(browsers)) {
    if (status) {
        let browserLower = browser.toLowerCase()
        let browserClass = browserLower.replace("is", "is-")
        body.classList.add(browserClass)
    }
}