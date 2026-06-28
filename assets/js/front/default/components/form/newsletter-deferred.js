/**
 * Deferred newsletter loader
 *
 * Fetches the newsletter fragment(s) after the main document is rendered and injects
 * them, then initialises the newsletter behaviour. The CSRF token (which opens a PHP
 * session) is therefore never rendered on the main document, so anonymous pages stay
 * cookieless and cacheable by the shared cache.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default class {
    constructor(els) {
        const list = els instanceof NodeList ? Array.from(els) : [els];
        list.forEach((el) => this.load(el));
    }

    load(el) {
        const url = el && el.dataset ? el.dataset.newsletterUrl : null;
        // Same-origin relative path only (set server-side via path()): the injected HTML is
        // our own trusted fragment, never an arbitrary URL.
        if (!url || !/^\/[^/]/.test(url) || el.dataset.newsletterLoaded === '1') {
            return;
        }
        el.dataset.newsletterLoaded = '1';

        fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})
            .then((response) => (response.ok ? response.text() : ''))
            .then((html) => {
                if (!html) {
                    return null;
                }
                el.innerHTML = html;

                return import(/* webpackPreload: true */ './newsletter').then((module) => new module.default());
            })
            .catch(() => {});
    }
}
