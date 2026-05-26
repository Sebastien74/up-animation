/**
 * Analytics tracker.
 *
 * Self-contained vanilla module. No dependencies, no cookies,
 * no persistent client storage. Posts anonymous events to /a/c.
 *
 * Hooks: pageview on load, click on [data-track] elements,
 * scroll milestones (25/50/75/100), form submit.
 *
 * Budget: < 5 KB gzip. Deferred load, never blocks DOMContentLoaded.
 */
(function () {
    'use strict';

    if (window.__upaAnalyticsLoaded) {
        return;
    }
    window.__upaAnalyticsLoaded = true;

    var ENDPOINT = '/a/c';
    var URL_MAX = 512;
    var LABEL_MAX = 120;
    var MILESTONES = [25, 50, 75, 100];

    var INTERACTIVE_SELECTOR = [
        'a[href]',
        'button',
        '[role="button"]',
        'input[type="submit"]',
        'input[type="button"]',
        '[data-bs-toggle]',
        '[data-track]'
    ].join(',');

    function send(data) {
        try {
            var body = JSON.stringify(data);
            if (typeof navigator !== 'undefined' && navigator.sendBeacon) {
                var blob = new Blob([body], { type: 'application/json' });
                if (navigator.sendBeacon(ENDPOINT, blob)) {
                    return;
                }
            }
            fetch(ENDPOINT, {
                method: 'POST',
                body: body,
                headers: { 'Content-Type': 'application/json' },
                keepalive: true,
                credentials: 'omit',
                cache: 'no-store'
            }).catch(function () {});
        } catch (_) {
            // Tracking must never throw into the host page.
        }
    }

    function currentPath() {
        var path = (location.pathname || '/') + (location.search || '');
        return path.length > URL_MAX ? path.slice(0, URL_MAX) : path;
    }

    function trackPageview() {
        send({
            type: 'pageview',
            url: currentPath(),
            referrer: document.referrer || null,
            locale: (document.documentElement.lang || '').slice(0, 8) || null,
            viewport: window.innerWidth + 'x' + window.innerHeight
        });
    }

    function normalizeLabel(value) {
        if (!value) {
            return '';
        }
        return String(value).replace(/\s+/g, ' ').trim().slice(0, LABEL_MAX);
    }

    function extractLabel(node) {
        return normalizeLabel(
            node.getAttribute('aria-label')
            || node.getAttribute('data-track')
            || node.getAttribute('title')
            || node.textContent
            || node.value
            || node.getAttribute('alt')
            || ''
        );
    }

    function extractAction(node) {
        var toggle = node.getAttribute('data-bs-toggle');
        if (toggle) {
            return toggle;
        }
        if (node.hasAttribute('data-track')) {
            return 'tracked';
        }
        if (node.tagName === 'A') {
            var href = node.getAttribute('href') || '';
            if (!href || href.charAt(0) === '#') {
                return 'anchor';
            }
            if (/^(mailto:|tel:|sms:)/i.test(href)) {
                return href.split(':')[0].toLowerCase();
            }
            var external = node.hostname && node.hostname !== location.hostname;
            return external ? 'outbound' : 'navigation';
        }
        if (node.tagName === 'BUTTON' || node.tagName === 'INPUT') {
            var type = (node.getAttribute('type') || 'button').toLowerCase();
            return type === 'submit' ? 'submit' : 'button';
        }
        return 'click';
    }

    function trackClick(event) {
        var node = event.target;
        if (!node || typeof node.closest !== 'function') {
            return;
        }
        var trigger = node.closest(INTERACTIVE_SELECTOR);
        if (!trigger) {
            return;
        }
        var label = extractLabel(trigger);
        if (!label) {
            return;
        }
        send({
            type: 'click',
            url: currentPath(),
            payload: {
                label: label,
                action: extractAction(trigger),
                tag: trigger.tagName.toLowerCase()
            }
        });
    }

    var firedMilestones = {};
    var scrollScheduled = false;

    function trackScroll() {
        scrollScheduled = false;
        var doc = document.documentElement;
        var total = doc.scrollHeight;
        var viewport = window.innerHeight;
        if (total <= viewport) {
            return;
        }
        var ratio = Math.round(((window.scrollY + viewport) / total) * 100);
        for (var i = 0; i < MILESTONES.length; i++) {
            var m = MILESTONES[i];
            if (ratio >= m && !firedMilestones[m]) {
                firedMilestones[m] = true;
                send({
                    type: 'scroll',
                    url: currentPath(),
                    payload: { milestone: m }
                });
            }
        }
    }

    function onScroll() {
        if (scrollScheduled) {
            return;
        }
        scrollScheduled = true;
        window.setTimeout(trackScroll, 250);
    }

    function trackForm(event) {
        var form = event.target;
        if (!form || form.tagName !== 'FORM') {
            return;
        }
        send({
            type: 'form',
            url: currentPath(),
            payload: {
                id: form.id || null,
                name: form.getAttribute('name') || null
            }
        });
    }

    function init() {
        trackPageview();
        document.addEventListener('click', trackClick, { capture: true, passive: true });
        document.addEventListener('submit', trackForm, { capture: true, passive: true });
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
