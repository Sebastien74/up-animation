import route from "../../vendor/components/routing";

/**
 * Remove data
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    let xHttp = new XMLHttpRequest()
    xHttp.open("GET", route('front_gdpr_remove_data'), true)
    xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8")
    xHttp.send()
}