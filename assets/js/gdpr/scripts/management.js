/**
 * Management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (modal = null) {

    let cookieName = 'felixCookies'

    let activeModalTrigger = function (element, status) {
        let el = document.getElementById(element)
        if (el) {
            el.onclick = function (e) {
                e.preventDefault()
                let checkboxes = document.getElementsByClassName('cookie-group-checkbox')
                for (let i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = status
                }
                let choiceValidate = document.querySelector('.gdpr-choices-validate')
                if (choiceValidate) {
                    choiceValidate.click()
                }
            }
        }
    }

    activeModalTrigger('gdpr-all-allowed', true)
    activeModalTrigger('gdpr-all-disallowed', false)

    let checkboxes = document.getElementsByClassName('cookie-group-checkbox')
    if (checkboxes) {
        for (let i = 0; i < checkboxes.length; i++) {
            let el = checkboxes[i]
            el.onchange = function () {
                let elId = el.getAttribute('id')
                let code = el.dataset.code
                let elements = document.querySelectorAll("input[data-code='" + code + "']")
                for (let j = 0; j < elements.length; j++) {
                    let elt = elements[j]
                    let eltId = elt.getAttribute('id')
                    if (eltId !== elId) {
                        elt.checked = el.checked
                    }
                }
            }
        }
    }

    let choiceValidates = document.getElementsByClassName('gdpr-choices-validate')
    for (let j = 0; j < choiceValidates.length; j++) {

        let choiceValidate = choiceValidates[j]

        if (choiceValidate) {

            choiceValidate.onclick = function (e) {

                let cookiesDenied = []
                let cookiesArray = []
                let haveDenied = false
                let checkboxes = document.getElementsByClassName('cookie-group-checkbox')
                let saveIcon = choiceValidate.querySelector('.spinner-border')

                if (saveIcon) {
                    saveIcon.classList.remove('d-none')
                }

                for (let i = 0; i < checkboxes.length; i++) {

                    let el = checkboxes[i]
                    let code = el.dataset.code
                    let service = el.dataset.service
                    let isConsent = el.checked

                    cookiesArray.push({slug: code, status: isConsent})

                    if (!isConsent) {
                        cookiesDenied.push({slug: code})
                        haveDenied = true
                    }

                    if (service !== "") {
                        /** Services activation */
                        import('./services-activation').then(({default: activeService}) => {
                            new activeService(service, code, isConsent)
                        }).catch(error => console.error(error.message));
                    }
                }

                /** Cookie create */
                import('../cookies/cookie-create').then(({default: createCookie}) => {
                    new createCookie(cookieName, JSON.stringify(cookiesArray))
                }).catch(error => console.error(error.message));

                HTMLElement.prototype.serialize = function () {
                    let obj = {}
                    let elements = this.querySelectorAll("input, select, textarea")
                    for (let i = 0; i < elements.length; ++i) {
                        let element = elements[i]
                        let name = element.name
                        let value = element.value
                        if (name) {
                            obj[name] = value
                        }
                    }
                    return JSON.stringify(obj)
                }

                let form = document.getElementById('gdpr-form-acceptation')
                let gdprData = form ? form.serialize() : null

                if (modal) {
                    let htmlElement = document.querySelector("html")
                    let body = document.body
                    body.classList.remove('gdpr-modal-open')
                    body.classList.remove('modal-open')
                    htmlElement.classList.remove('overflow-hidden')
                    modal.hide()
                    document.getElementById('gdpr-modal').remove()
                }

                if (haveDenied) {
                    import('../cookies/cookies-remove').then(({default: removeCookiesDB}) => {
                        new removeCookiesDB(cookiesDenied)
                    }).catch(error => console.error(error.message));
                } else {
                    /** Scripts loader */
                    import('./scripts-loader').then(({default: scriptsLoader}) => {
                        new scriptsLoader(true, saveIcon, haveDenied, gdprData, true)
                    }).catch(error => console.error(error.message));
                }
            }
        }
    }
}