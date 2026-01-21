import Cookies from 'js-cookie'

/**
 * Cookie remove
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (name) {

    let domainName = document.domain
    let domain = domainName.replace('www.', '')

    Cookies.remove(name)

    Cookies.remove(name, {path: ''})
    Cookies.remove(name, {path: '', domain: domain})

    Cookies.remove(name, {path: '/'})
    Cookies.remove(name, {path: '/', domain: domain})

    Cookies.remove(name, {path: '/'})
    Cookies.remove(name, {path: '/', domain: '.' + domain})
}