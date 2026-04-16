/**
 * Axeptio.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

let body = document.body;
let slimSrc = true;
let clientId = body.dataset.axeptio;
let gtmInjection = body.dataset.axeptioExternal;
let cookiesVersion = body.dataset.axeptioCookie;

if (clientId && !gtmInjection) {

    let options = {};
    options.clientId = clientId;
    options.userCookiesDuration = 180;
    if (cookiesVersion) {
        options.cookiesVersion = cookiesVersion;
    }
    options.googleConsentMode = {
        default: {
            analytics_storage: "denied",
            ad_storage: "denied",
            ad_user_data: "denied",
            ad_personalization: "denied",
            wait_for_update: 500
        }
    }

    (function (d, s) {
        let t = d.getElementsByTagName(s)[0], e = d.createElement(s);
        e.async = true;
        e.src = slimSrc ? "//static.axept.io/sdk-slim.js" : "//static.axept.io/sdk.js";
        t.parentNode.insertBefore(e, t);
    })(document, "script");
}

if (clientId || gtmInjection) {

    /**
     * Check if a block should be refreshed.
     */
    let refresh = function (wrap, code, active) {
        if (code === 'youtube' && wrap.querySelector('.embed-youtube') && active) {
            return false;
        }
        return true;
    };

    /**
     * Dynamically load a script element.
     *
     * @param {HTMLElement} sourceEl
     */
    function loadDeferredScript(sourceEl) {
        if (!sourceEl || sourceEl.dataset.loaded === '1') {
            return;
        }
        let src = sourceEl.getAttribute('data-src');
        if (!src) {
            return;
        }
        let script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.type = 'text/javascript';
        let nonce = sourceEl.getAttribute('nonce');
        if (nonce) {
            script.setAttribute('nonce', nonce);
        }
        let webchatConfigId = sourceEl.getAttribute('webchatconfigid');
        if (webchatConfigId) {
            script.setAttribute('webchatconfigid', webchatConfigId);
        }
        script.onload = function () {
            sourceEl.dataset.loaded = '1';
        };
        document.head.appendChild(script);
    }

    window._axcb = window._axcb || [];
    window._axcb.push(function (sdk) {
        sdk.on("cookies:complete", function (choices) {

            document.querySelectorAll("[data-axeptio-consent]").forEach(function (consentEl) {
                let vendor = consentEl.getAttribute('data-axeptio-consent');
                let active = choices[vendor];
                let wrap = consentEl.closest('[data-code]');
                if (wrap) {
                    let code = wrap.dataset.code;
                    let node = document.createElement('div');
                    node.innerHTML = active ? wrap.dataset.prototype : wrap.dataset.prototypePlaceholder;
                    let pushEl = node.firstElementChild;
                    if (refresh(wrap, code, active)) {
                        wrap.innerHTML = pushEl.outerHTML;
                        if (code === 'youtube') {
                            let videos = wrap.querySelectorAll('.embed-youtube');
                            if (videos) {
                                import(/* webpackPreload: true */ '../vendor/components/lazy-videos').then(({lazyVideos: LazyVideos}) => {
                                    new LazyVideos(videos)
                                }).catch(error => console.error(error.message));
                            }
                        } else if (code === 'apps-elfsight') {
                            let socialWalls = wrap.querySelectorAll('.social-wall-wrap')
                            if (socialWalls.length > 0) {
                                import(/* webpackPreload: true */ '../front/default/components/social-wall').then(({default: socialWallsModule}) => {
                                    new socialWallsModule()
                                }).catch(error => console.error(error.message));
                            }
                        }
                    }
                }
            });

            let masonry = body.querySelectorAll('[data-component="masonry"]')
            if (masonry.length > 0) {
                import(/* webpackPreload: true */ '../front/default/components/masonry').then(({default: masonryPlugin}) => {
                    new masonryPlugin(masonry)
                }).catch(error => console.error(error.message));
            }

            let hideConsentEls = document.querySelectorAll("[data-hide-on-vendor-consent]");
            for (let i = 0; i < hideConsentEls.length; i++) {
                let hideEl = hideConsentEls[i];
                let hideVendor = hideEl.getAttribute("data-hide-on-vendor-consent");
                hideEl.style.display = !choices[hideVendor] ? "none" : "inherit";
            }

            let requireConsentEls = document.querySelectorAll("[data-requires-vendor-consent]");
            for (let i = 0; i < requireConsentEls.length; i++) {
                let requireEl = requireConsentEls[i];
                let requireVendor = requireEl.getAttribute("data-requires-vendor-consent");
                let deletableId = requireEl.getAttribute("data-deletable");
                let deletableEl = deletableId ? document.querySelector(deletableId) : null;
                if (choices[requireVendor]) {
                    let asCustom = requireEl.getAttribute('data-custom-script') && !requireEl.classList.contains('loaded');
                    if (deletableEl) {
                        deletableEl.classList.remove('d-none');
                    }
                    loadDeferredScript(requireEl);
                    if (asCustom) {
                        loadScript(requireEl.getAttribute('data-custom-script'));
                        requireEl.classList.add('loaded');
                    }
                } else {
                    if (deletableEl) {
                        deletableEl.classList.add('d-none');
                    }
                }
            }
        });
    });

    /**
     * Load an external script.
     */
    function loadScript(src) {
        let script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = src;
        document.getElementsByTagName('head')[0].appendChild(script);
    }
}