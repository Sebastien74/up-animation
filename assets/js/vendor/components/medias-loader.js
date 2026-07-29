import DOMPurify from 'dompurify';

/**
 * Media loader
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    const body = document.body;
    const skinAdmin = body.classList.contains('skin-admin');
    const loader = skinAdmin ? document.getElementById('main-preloader') : null;

    const loaderRequest = function () {
        const el = document.querySelector('hx\\:include.hx-include-in-viewport');

        if (loader && el) {
            loader.classList.remove('d-none');
            loader.style.opacity = '1';
        }

        if (el && !body.classList.contains('media-loader-active')) {
            body.classList.add('media-loader-active');

            const xHttp = new XMLHttpRequest();
            xHttp.open("GET", el.getAttribute('src'), true);
            xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");

            xHttp.onload = function () {
                if (xHttp.status === 200) {
                    if (!el.classList.contains('only-hx')) {
                        const response = JSON.parse(xHttp.response);
                        const loaderWrap = el.closest('.img-loader-wrap');
                        if (loaderWrap) {
                            const innerLoader = loaderWrap.querySelector('.img-loader');
                            // Trusted Types (CSP `require-trusted-types-for 'script'`) : innerHTML
                            // exige un TrustedHTML. DOMPurify crée à la volée la policy `dompurify`
                            // (déjà whitelistée dans SecurityPolicySubscriber) et renvoie un TrustedHTML.
                            loaderWrap.innerHTML = DOMPurify.sanitize(response.html, {RETURN_TRUSTED_TYPE: true});
                            if (innerLoader) {
                                innerLoader.remove();
                            }
                        }
                    } else {
                        el.remove();
                    }
                    body.classList.remove('media-loader-active');
                    // Small delay to let the browser breathe before next request
                    setTimeout(loaderRequest, 50);
                }
            };
            xHttp.onerror = () => body.classList.remove('media-loader-active');
            xHttp.send();
        } else if (!el && loader) {
            loader.classList.add('d-none');
            loader.style.opacity = '0';
        }
    }

    const checkViewport = function () {
        const els = document.querySelectorAll('hx\\:include:not(.hx-include-in-viewport)');
        let changed = false;
        els.forEach(function (el) {
            const rect = el.getBoundingClientRect();
            const isIn = rect.top < (window.innerHeight || document.documentElement.clientHeight) + 300 && rect.bottom > -300;
            if (isIn) {
                el.classList.add('hx-include-in-viewport');
                changed = true;
            }
        });
        if (changed || document.querySelector('hx\\:include.hx-include-in-viewport')) {
            loaderRequest();
        }
    }

    checkViewport();

    let scheduled = false;
    window.addEventListener('scroll', () => {
        if (!scheduled) {
            scheduled = true;
            requestAnimationFrame(() => {
                checkViewport();
                scheduled = false;
            });
        }
    }, { passive: true });
};