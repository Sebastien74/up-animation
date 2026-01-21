/**
 * Emails & phones decrypt
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let dataEl = document.getElementById('data-path')

    setTimeout(function () {

        /** Emails */
        let emails = document.querySelectorAll('a[data-mailto]')
        for (let i = 0; i < emails.length; i++) {
            let el = emails[i]
            let mailto = el.dataset.mailto
            let website = el.dataset.id
            let elText = el.getElementsByClassName("email-text")[0]
            if (typeof mailto != "undefined") {
                let xHttp = new XMLHttpRequest()
                let url = dataEl.dataset.decrypt + '/' + website + '/' + mailto
                xHttp.open("GET", url, true)
                xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8")
                xHttp.send()
                xHttp.onload = function (e) {
                    if (this.readyState === 4 && this.status === 200) {
                        let response = JSON.parse(this.response)
                        if (response.result !== false) {
                            el.setAttribute("href", "mailto:" + response.result)
                            if (elText) {
                                elText.innerHTML = response.result
                            }
                            el.classList.remove('loading')
                        }
                    }
                }
            }
        }

        /** Phones */
        let phones = document.querySelectorAll('a[data-tel]')
        for (let i = 0; i < phones.length; i++) {
            let el = phones[i]
            let website = el.dataset.id
            let telTo = el.dataset.tel
            let telText = el.dataset.text
            let elText = el.getElementsByClassName("phone-text")[0]
            el.onclick = function (event) {
                /** Facebook track */
                if (el.classList.contains('fb-phone-track')) {
                    fbq('track', 'Contact')
                }
                if (el.classList.contains('has-desktop')) {
                    event.preventDefault()
                }
            }
            if (typeof telTo != "undefined") {
                let xHttp = new XMLHttpRequest()
                let url = dataEl.dataset.decrypt + '/' + website + '/' + telTo
                xHttp.open("GET", url, true)
                xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8")
                xHttp.send()
                xHttp.onload = function (e) {
                    if (this.readyState === 4 && this.status === 200) {
                        let response = JSON.parse(this.response)
                        if (response.result !== false) {
                            el.setAttribute("href", "tel:" + response.result)
                            if (typeof telText != "undefined") {
                                let xHttp = new XMLHttpRequest()
                                let url = dataEl.dataset.decrypt + '/' + website + '/' + telText
                                xHttp.open("GET", url, true)
                                xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8")
                                xHttp.send()
                                xHttp.onload = function (e) {
                                    if (this.readyState === 4 && this.status === 200) {
                                        let response = JSON.parse(this.response)
                                        if (response.result !== false) {
                                            elText.innerHTML = response.result
                                            el.classList.remove('loading')
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }, 10)
}