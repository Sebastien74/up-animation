/**
 * Accessibility widget
 *
 * Floating visitor-facing accessibility toolbar. Preferences are applied as
 * classes on the <html> element and persisted in localStorage so they survive
 * navigation and reloads.
 *
 * @copyright 2026
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 * @licence under the MIT License (LICENSE.txt)
 */

const FONT_STEPS = [100, 115, 130, 150];

export default function () {

    const widget = document.getElementById('a11y-widget');
    if (!widget) {
        return;
    }

    /** Styles are bundled in a dedicated chunk, loaded only when the module is active. */
    import('../../../../scss/front/default/components/accessibility-widget.scss');

    const html = document.documentElement;
    const toggleBtn = widget.querySelector('#a11y-toggle');
    const closeBtn = widget.querySelector('#a11y-close');
    const panel = widget.querySelector('#a11y-panel');
    const fontValueEl = widget.querySelector('[data-a11y-font-value]');
    const storageKey = widget.dataset.a11yStorageKey || 'a11y-prefs';

    /** Boolean toggles, mapped to the class added on <html>. */
    const TOGGLES = {
        'contrast': 'a11y-contrast',
        'readable-font': 'a11y-readable-font',
        'spacing': 'a11y-spacing',
        'underline-links': 'a11y-underline-links',
        'reduce-motion': 'a11y-reduce-motion',
        'big-cursor': 'a11y-big-cursor',
        'reading-guide': 'a11y-reading-guide',
    };

    /** Default preferences. */
    const defaults = {
        fontStep: 0,
        contrast: false,
        'readable-font': false,
        spacing: false,
        'underline-links': false,
        'reduce-motion': false,
        'big-cursor': false,
        'reading-guide': false,
    };

    let prefs = load();
    let guideEl = null;
    let guideHandler = null;

    /** Read preferences from storage, falling back to defaults. */
    function load() {
        try {
            const raw = window.localStorage.getItem(storageKey);
            return raw ? Object.assign({}, defaults, JSON.parse(raw)) : Object.assign({}, defaults);
        } catch (e) {
            return Object.assign({}, defaults);
        }
    }

    /** Persist preferences to storage. */
    function save() {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(prefs));
        } catch (e) { /* storage unavailable: keep working without persistence */ }
    }

    /** Apply the current preferences to the document. */
    function apply() {

        // Font scaling
        const step = Math.min(Math.max(prefs.fontStep, 0), FONT_STEPS.length - 1);
        prefs.fontStep = step;
        if (step > 0) {
            html.classList.add('a11y-font-scaled');
            html.style.setProperty('--a11y-font-scale', (FONT_STEPS[step] / 100).toString());
        } else {
            html.classList.remove('a11y-font-scaled');
            html.style.removeProperty('--a11y-font-scale');
        }
        if (fontValueEl) {
            fontValueEl.textContent = FONT_STEPS[step] + '%';
        }

        // Boolean toggles
        Object.keys(TOGGLES).forEach((key) => {
            html.classList.toggle(TOGGLES[key], !!prefs[key]);
        });

        toggleReadingGuide(!!prefs['reading-guide']);
    }

    /** Reflect the current preferences in the panel controls. */
    function syncControls() {
        widget.querySelectorAll('[data-a11y-toggle]').forEach((input) => {
            input.checked = !!prefs[input.dataset.a11yToggle];
        });
    }

    /** Reading guide: a horizontal ruler that follows the pointer. */
    function toggleReadingGuide(enabled) {
        if (enabled && !guideEl) {
            guideEl = document.createElement('div');
            guideEl.className = 'a11y-reading-guide-bar';
            guideEl.setAttribute('aria-hidden', 'true');
            document.body.appendChild(guideEl);
            guideHandler = (event) => {
                const y = event.touches ? event.touches[0].clientY : event.clientY;
                guideEl.style.transform = 'translateY(' + y + 'px)';
            };
            window.addEventListener('mousemove', guideHandler, { passive: true });
            window.addEventListener('touchmove', guideHandler, { passive: true });
        } else if (!enabled && guideEl) {
            window.removeEventListener('mousemove', guideHandler);
            window.removeEventListener('touchmove', guideHandler);
            guideEl.remove();
            guideEl = null;
            guideHandler = null;
        }
    }

    /** Open / close the panel. */
    function openPanel() {
        panel.hidden = false;
        widget.classList.add('a11y-open');
        toggleBtn.setAttribute('aria-expanded', 'true');
        const firstControl = panel.querySelector('button, input');
        if (firstControl) {
            firstControl.focus();
        }
        document.addEventListener('keydown', onKeydown);
        document.addEventListener('click', onClickOutside, true);
    }

    function closePanel(refocus = true) {
        panel.hidden = true;
        widget.classList.remove('a11y-open');
        toggleBtn.setAttribute('aria-expanded', 'false');
        document.removeEventListener('keydown', onKeydown);
        document.removeEventListener('click', onClickOutside, true);
        if (refocus) {
            toggleBtn.focus();
        }
    }

    function onKeydown(event) {
        if (event.key === 'Escape') {
            closePanel();
        }
    }

    function onClickOutside(event) {
        if (!widget.contains(event.target)) {
            closePanel(false);
        }
    }

    /** Reset everything to defaults. */
    function reset() {
        prefs = Object.assign({}, defaults);
        save();
        apply();
        syncControls();
    }

    // --- Wiring -------------------------------------------------------------

    toggleBtn.addEventListener('click', () => {
        if (panel.hidden) {
            openPanel();
        } else {
            closePanel();
        }
    });

    closeBtn.addEventListener('click', () => closePanel());

    widget.querySelectorAll('[data-a11y-action]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const action = btn.dataset.a11yAction;
            if (action === 'font-up') {
                prefs.fontStep += 1;
            } else if (action === 'font-down') {
                prefs.fontStep -= 1;
            } else if (action === 'reset') {
                reset();
                return;
            }
            save();
            apply();
        });
    });

    widget.querySelectorAll('[data-a11y-toggle]').forEach((input) => {
        input.addEventListener('change', () => {
            prefs[input.dataset.a11yToggle] = input.checked;
            save();
            apply();
        });
    });

    // --- Init ---------------------------------------------------------------

    apply();
    syncControls();
}
