/**
 * Log errors
 */
export default function () {

    let html = document.documentElement;
    let isDebug = typeof html.dataset.debug !== 'undefined' && parseInt(html.dataset.debug) === 1;

    if (!isDebug) {

        window.onerror = function (messageOrEvent, source, lineno, colno, error) {

            try {

                function fnBrowserDetect() {
                    let userAgent = navigator.userAgent;
                    let browserName;
                    if (userAgent.match(/chrome|chromium|crios/i)) {
                        browserName = "chrome";
                    } else if (userAgent.match(/firefox|fxios/i)) {
                        browserName = "firefox";
                    } else if (userAgent.match(/safari/i)) {
                        browserName = "safari";
                    } else if (userAgent.match(/opr\//i)) {
                        browserName = "opera";
                    } else if (userAgent.match(/edg/i)) {
                        browserName = "edge";
                    } else {
                        browserName = "No browser detection";
                    }
                    return browserName;
                }

                if (source !== '' && lineno !== 0 && colno !== 0) {
                    let url = '/core/dev/logger/javascript/errors'
                        + '?browser=' + encodeURIComponent((fnBrowserDetect()).toString().substring(0, 150))
                        + '&message=' + encodeURIComponent((messageOrEvent || '').toString().substring(0, 150))
                        + '&source=' + encodeURIComponent((source || '').toString().substring(0, 150))
                        + '&line=' + encodeURIComponent((lineno || '').toString().substring(0, 150))
                        + '&col=' + encodeURIComponent((colno || '').toString().substring(0, 150))
                        + '&url=' + encodeURIComponent((window.location.href || '').toString().substring(0, 150));
                    url = url.indexOf(window.location.host) === -1 ? url.replace(location.protocol, "") : url;
                    let xHttp = new XMLHttpRequest();
                    xHttp.open("GET", url, true);
                    xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
                    xHttp.send();
                }

                console.log(messageOrEvent);

            } catch (e) {
                console.log(e);
            }

            return true;
        }
    }
}