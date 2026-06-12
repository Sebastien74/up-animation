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

    /** Widget UI styles (button/panel): always needed when the widget is present.
        The widget stays hidden (d-none) until the chunk is applied, to avoid a flash
        of the unstyled button at the bottom of the page during load. */
    const reveal = () => widget.classList.remove('d-none');
    import('../../../../scss/front/default/components/accessibility-widget.scss')
        .then(reveal)
        .catch(reveal);

    /** Preference application styles (contrast, dyslexic font, spacing, grayscale…):
        loaded only once, and only when at least one preference is actually active, so
        nothing extra is shipped to visitors who never use the widget. */
    let prefsCssLoaded = false;
    function ensurePrefsCss() {
        if (prefsCssLoaded) {
            return;
        }
        prefsCssLoaded = true;
        import('../../../../scss/front/default/components/accessibility-prefs.scss');
    }

    const html = document.documentElement;
    const toggleBtn = widget.querySelector('#a11y-toggle');
    const closeBtn = widget.querySelector('#a11y-close');
    const panel = widget.querySelector('#a11y-panel');
    const fontValueEl = widget.querySelector('[data-a11y-font-value]');
    const fontDownBtn = widget.querySelector('[data-a11y-action="font-down"]');
    const fontUpBtn = widget.querySelector('[data-a11y-action="font-up"]');
    const resetBtn = widget.querySelector('#a11y-reset');
    const storageKey = widget.dataset.a11yStorageKey || 'a11y-prefs';

    /** Boolean toggles, mapped to the class added on <html>. */
    const TOGGLES = {
        'contrast': 'a11y-contrast',
        'readable-font': 'a11y-readable-font',
        'spacing': 'a11y-spacing',
        'underline-links': 'a11y-underline-links',
        'text-left': 'a11y-text-left',
        'reduce-motion': 'a11y-reduce-motion',
        'desaturate': 'a11y-desaturate',
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
        'text-left': false,
        'reduce-motion': false,
        'desaturate': false,
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
        if (fontDownBtn) {
            fontDownBtn.disabled = step <= 0;
        }
        if (fontUpBtn) {
            fontUpBtn.disabled = step >= FONT_STEPS.length - 1;
        }

        // Boolean toggles
        Object.keys(TOGGLES).forEach((key) => {
            html.classList.toggle(TOGGLES[key], !!prefs[key]);
        });

        toggleReadingGuide(!!prefs['reading-guide']);
        applyMotionPause(!!prefs['reduce-motion']);
        updateResetState();

        if (hasNonThemeSettings() || document.body.classList.contains('as-accessibility')) {
            ensurePrefsCss();
        }
    }

    /** True when a non-theme preference differs from default (zoom or a toggle). */
    function hasNonThemeSettings() {
        return prefs.fontStep > 0 || Object.keys(TOGGLES).some((key) => !!prefs[key]);
    }

    /** True when at least one preference differs from default, or the site is in
        dark mode (which the reset would also revert). */
    function hasActiveSettings() {
        return hasNonThemeSettings()
            || !!(resetBtn && resetBtn.dataset.a11yResetLightUrl && html.dataset.theme === 'dark');
    }

    /** Dim / disable the reset button when there is nothing to reset. */
    function updateResetState() {
        if (resetBtn) {
            resetBtn.disabled = !hasActiveSettings();
        }
    }

    /** Pause / resume auto-playing media. Carousels and sliders listen to the
        `a11y:motion` event (CSS animations are paused via the html class). */
    function applyMotionPause(paused) {
        if (paused) {
            document.querySelectorAll('video, audio').forEach((media) => {
                if (!media.paused) {
                    media.pause();
                }
            });
        }
        document.dispatchEvent(new CustomEvent('a11y:motion', { detail: { paused } }));
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

    /** Collapse every accordion group (compact panel on each open). */
    function collapseAccordions() {
        widget.querySelectorAll('.a11y-accordion-toggle').forEach((btn) => {
            btn.setAttribute('aria-expanded', 'false');
            const accordionPanel = document.getElementById(btn.getAttribute('aria-controls'));
            if (accordionPanel) {
                accordionPanel.hidden = true;
            }
        });
    }

    /** Open / close the panel. */
    function openPanel() {
        collapseAccordions();
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

    /** Reset everything to defaults. Also returns the site to the light theme
        (default) when it is currently in dark mode, via the existing mechanism. */
    function reset() {
        prefs = Object.assign({}, defaults);
        save();
        apply();
        syncControls();
        const resetBtn = widget.querySelector('#a11y-reset');
        const lightUrl = resetBtn ? resetBtn.dataset.a11yResetLightUrl : null;
        if (lightUrl && html.dataset.theme === 'dark') {
            widget.classList.add('a11y-busy');
            window.location.href = lightUrl;
        }
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

    /** Collapsible groups (accordions). */
    widget.querySelectorAll('.a11y-accordion-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            const panel = document.getElementById(btn.getAttribute('aria-controls'));
            const expanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            if (panel) {
                panel.hidden = expanded;
            }
        });
    });

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

    /** Dark mode delegates to the existing server-side theme mechanism (FRONT_THEME
        cookie + dedicated CSS bundle): the change navigates, it is not a client class. */
    const themeInput = widget.querySelector('[data-a11y-theme-url]');
    if (themeInput) {
        themeInput.addEventListener('change', () => {
            /* Instant feedback before the reload: enabling dark activates the reset,
               disabling it dims the reset again unless another setting is active. */
            if (resetBtn) {
                resetBtn.disabled = !(themeInput.checked || hasNonThemeSettings());
            }
            const option = themeInput.closest('.a11y-option');
            if (option) {
                option.classList.add('a11y-loading');
            }
            widget.classList.add('a11y-busy');
            window.location.href = themeInput.dataset.a11yThemeUrl;
        });
    }

    // --- Init ---------------------------------------------------------------

    apply();
    syncControls();
}
