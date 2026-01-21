/**
 * Matomo.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

const body = document.body;
const matomoId = body.dataset.matomoId;
const matomoUrl = body.dataset.matomoUrl ? body.dataset.matomoUrl : false; // matomo.agence-felix.fr

if (matomoId && matomoUrl) {
    let matomoLoaded = false;
    let _paq = window._paq = window._paq || [];
    /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
    _paq.push(['requireCookieConsent']);
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);
    (_axcb = window._axcb || []).push(function (sdk) {
        sdk.on("cookies:complete", function (choices) {
            if (choices.matomo === true) {
                _paq.push(['setCookieConsentGiven']);
                _paq.push(['rememberCookieConsentGiven']);
                _paq.push(['enableBrowserFeatureDetection']);
            } else {
                _paq.push(['forgetConsentGiven']);
                _paq.push(['forgetCookieConsentGiven']);
                _paq.push(['disableBrowserFeatureDetection']);
            }
            if (!matomoLoaded) {
                (function() {
                    let u="//" + matomoUrl + "/";
                    _paq.push(['setTrackerUrl', u+'matomo.php']);
                    _paq.push(['setSiteId', matomoId]);
                    let d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
                    g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
                })();
                matomoLoaded = true;
            }
        });
    });
    if (!matomoLoaded) {
        _paq.push(['forgetConsentGiven']);
        _paq.push(['forgetCookieConsentGiven']);
        _paq.push(['disableBrowserFeatureDetection']);
        (function() {
            let u="//" + matomoUrl + "/";
            _paq.push(['setTrackerUrl', u+'matomo.php']);
            _paq.push(['setSiteId', matomoId]);
            let d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
            g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
        })();
    }
}