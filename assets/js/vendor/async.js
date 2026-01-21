/**
 * Async resources
 *
 * @copyright 2020
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @version 1.0
 * @licence under the MIT License (LICENSE.txt)
 */

let onLoadStylesheets = function () {
    let head = document.getElementsByTagName('head');
    let stylesheets = head[0].querySelectorAll("link[as='style']")
    stylesheets.forEach((stylesheet, index) => {
        stylesheet.setAttribute('rel', 'stylesheet')
        stylesheet.removeAttribute('as')
    })
}

let onLoadJavaScripts = function () {

    let scripts = document.querySelectorAll('[data-as="script"]')
    let comment = null

    for (let i = 0; i < scripts.length; ++i) {

        if (scripts[i].getAttribute('data-comment') !== comment) {
            comment = scripts[i].getAttribute('data-comment')
            let commentEl = document.createComment(comment + " javaScript")
            document.body.appendChild(commentEl)
        }

        scripts[i].removeAttribute('data-comment');

        let usedLaterScript = document.createElement('script')
        usedLaterScript.defer = true
        usedLaterScript.src = scripts[i].getAttribute('data-href')
        usedLaterScript.setAttribute('nonce', scripts[i].getAttribute('data-nonce'))
        usedLaterScript.setAttribute('crossorigin', scripts[i].getAttribute('data-crossorigin'))
        document.body.appendChild(usedLaterScript)
        scripts[i].remove()
    }
}

window.addEventListener("load", function () {
    onLoadStylesheets()
    onLoadJavaScripts()
})