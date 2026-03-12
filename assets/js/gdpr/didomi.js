(function () {

    function i(e) {
        if (!window.frames[e]) {
            if (document.body && document.body.firstChild) {
                var t = document.body;
                var n = document.createElement("iframe");
                n.style.display = "none";
                n.name = e;
                n.title = e;
                t.insertBefore(n, t.firstChild)
            } else {
                setTimeout(function () {
                    i(e)
                }, 5)
            }
        }
    }

    function e(n, o, r, f, s) {

        function e(e, t, n, i) {
            if (typeof n !== "function") {
                return
            }
            if (!window[o]) {
                window[o] = []
            }
            var a = false;
            if (s) {
                a = s(e, i, n)
            }
            if (!a) {
                window[o].push({command: e, version: t, callback: n, parameter: i})
            }
        }

        e.stub = true;
        e.stubVersion = 2;

        function t(i) {
            if (!window[n] || window[n].stub !== true) {
                return
            }
            if (!i.data) {
                return
            }
            var a = typeof i.data === "string";
            var e;
            try {
                e = a ? JSON.parse(i.data) : i.data
            } catch (t) {
                return
            }
            if (e[r]) {
                var o = e[r];
                window[n](o.command, o.version, function (e, t) {
                    var n = {};
                    n[f] = {returnValue: e, success: t, callId: o.callId};
                    if (i.source) {
                        i.source.postMessage(a ? JSON.stringify(n) : n, "*")
                    }
                }, o.parameter)
            }
        }

        if (typeof window[n] !== "function") {
            window[n] = e;
            if (window.addEventListener) {
                window.addEventListener("message", t, false)
            } else {
                window.attachEvent("onmessage", t)
            }
        }
    }

    e("__tcfapi", "__tcfapiBuffer", "__tcfapiCall", "__tcfapiReturn");
    i("__tcfapiLocator")
})();

(function () {
    (function (e) {
        var r = document.createElement("link");
        r.rel = "preconnect";
        r.as = "script";
        var t = document.createElement("link");
        t.rel = "dns-prefetch";
        t.as = "script";
        var n = document.createElement("script");
        n.id = "spcloader";
        n.type = "text/javascript";
        n["async"] = true;
        n.charset = "utf-8";
        var o = "https://sdk.privacy-center.org/" + e + "/loader.js?target=" + document.location.hostname;
        if (window.didomiConfig && window.didomiConfig.user) {
            var i = window.didomiConfig.user;
            var a = i.country;
            var c = i.region;
            if (a) {
                o = o + "&country=" + a;
                if (c) {
                    o = o + "&region=" + c
                }
            }
        }
        r.href = "https://sdk.privacy-center.org/";
        t.href = "https://sdk.privacy-center.org/";
        n.src = o;
        var d = document.getElementsByTagName("script")[0];
        d.parentNode.insertBefore(r, d);
        d.parentNode.insertBefore(t, d);
        d.parentNode.insertBefore(n, d)
    })("ceb0b1ff-fd81-4c1f-a089-52a29d41ed35")
})();

function initMatomo(hasConsent) {

    let body = document.body;
    let matomoId = body.dataset.matomoId;
    let matomoUrl = body.dataset.matomoUrl ? body.dataset.matomoUrl : 'matomo.agence-felix.fr';
    let matomoLoaded = body.classList.contains("matomo-loaded");

    if (matomoId && matomoUrl) {
        let _paq = window._paq = window._paq || [];
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        if (hasConsent) {
            _paq.push(['setCookieConsentGiven']);
            _paq.push(['rememberCookieConsentGiven']);
            _paq.push(['enableBrowserFeatureDetection']);
        } else {
            _paq.push(['forgetConsentGiven']);
            _paq.push(['forgetCookieConsentGiven']);
            _paq.push(['disableBrowserFeatureDetection']);
        }
        if (!matomoLoaded) {
            (function () {
                var u = "//matomo.agence-felix.fr/";
                _paq.push(['setTrackerUrl', u + 'matomo.php']);
                _paq.push(['setSiteId', matomoId]);
                var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
                g.async = true;
                g.src = u + 'matomo.js';
                s.parentNode.insertBefore(g, s);
            })();
            body.classList.add("matomo-loaded");
        }
    }
}

function initClarity(hasConsent) {
    if (hasConsent && !document.body.classList.contains('clarity-loaded')) {
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "ryrcxp1tqi");
        document.body.classList.add('clarity-loaded');
    }
}

function didomiCookies(Didomi) {
    const purposes = Didomi.getCurrentUserStatus().purposes;
    const jsonString = JSON.stringify(purposes);
    const expires = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toUTCString();
    document.cookie = `DidomiCookies=${encodeURIComponent(jsonString)}; expires=${expires}; path=/; Secure; SameSite=Strict`;
    return purposes;
}

function didomiCookie(name) {
    return Didomi.getCurrentUserStatus().purposes[name]?.enabled;
}

function displayService(service, code, active) {
    document.querySelectorAll('.gdpr-' + service + '-wrap').forEach(el => {
        let wrapCode = el.dataset.code
        if (wrapCode === code && active) {
            el.innerHTML = el.dataset.prototype
        } else if (wrapCode === code && !active) {
            el.innerHTML = el.dataset.prototypePlaceholder
        }
    });
}

function analytics() {
    const asMatomoConsent = didomiCookie('matomo-purpose');
    const asClarityConsent = didomiCookie('clarity-purpose');
    initMatomo(asMatomoConsent);
    initClarity(asClarityConsent);
}

function displayYoutube(Didomi) {
    // const youTubeEnabled = Didomi.getCurrentUserStatus().vendors['youtube-M648BRd7']?.enabled;
    const youTubeEnabled = didomiCookie('measure_content_performance')
        && didomiCookie('create_content_profile')
        && didomiCookie('geo_marketing_studies')
        && didomiCookie('select_personalized_content')
        && didomiCookie('geolocation_data');
    displayService('player', 'youtube', youTubeEnabled);
    document.querySelectorAll("[data-axeptio-consent]").forEach(function (consentEl) {
        const wrap = consentEl.closest('[data-code]');
        if (wrap) {
            const videos = wrap.querySelectorAll('.embed-youtube');
            if (videos) {
                import(/* webpackPreload: true */ '../vendor/components/lazy-videos').then(({lazyVideos: LazyVideos}) => {
                    new LazyVideos(videos)
                }).catch(error => console.error(error.message));
            }
        }
    });
}

window.didomiOnReady = window.didomiOnReady || [];
window.didomiOnReady.push(function (Didomi) {
    analytics(Didomi);
    displayYoutube(Didomi);
    didomiCookies(Didomi);
    Didomi.on('consent.changed', function () {
        analytics(Didomi);
        displayYoutube(Didomi);
        didomiCookies(Didomi);
    });
});