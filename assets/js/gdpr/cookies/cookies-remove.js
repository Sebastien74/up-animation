import route from "../../vendor/components/routing"
import removeCookie from "./cookie-remove"

/**
 * Cookies remove
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (cookiesDenied) {
    for (const [key, obj] of Object.entries(cookiesDenied)) {
        let xHttp = new XMLHttpRequest()
        xHttp.open("GET", route('front_gdpr_cookies_db', {slug: obj.slug, _format: 'json'}), true)
        xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8")
        xHttp.send()
        xHttp.onload = function (e) {
            if (this.readyState === 4 && this.status === 200) {
                let response = JSON.parse(this.response)
                let cookies = response.cookies
                for (const [cookieKey, name] of Object.entries(cookies)) {
                    removeCookie(name)
                    removeCookie(name)
                }
                if (parseInt(key) === 0) {
                    xHttp.open("GET", route('front_gdpr_cookies_db', {slug: obj.slug, _format: 'json'}), true)
                    xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8")
                    xHttp.send()
                    xHttp.onload = function (e) {
                        location.reload()
                    }
                }
            }
        }
    }
}