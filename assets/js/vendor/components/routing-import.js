const routes = require('../../../../public/js/fos_js_routes.json');

/**
 * Fos JS Routing
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (Routing, route = null, params = {}) {

    if (!route) {
        return false;
    }

    Routing.setRoutingData(routes);

    let url = Routing.generate(route, params);
    let baseUrl = window.location.host;
    let protocol = location.protocol;

    if (url.indexOf('localhost') > 0) {
        url = url.replace('localhost', baseUrl);
    }

    if (url.indexOf(baseUrl) === -1 && url.indexOf(protocol) === -1) {
        url = window.location.origin + url;
    } else if (url.indexOf(baseUrl) === -1) {
        url = baseUrl + url;
    } else if (url.indexOf(protocol) === -1) {
        url = protocol + '//' + url;
    }

    url = url.replace('http://https:', protocol);
    url = url.replace('https://http:', protocol);

    return url;
}