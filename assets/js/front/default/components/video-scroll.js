export default function () {

    const triggers = Array.from(document.querySelectorAll('.block-scroll-video'));
    if (!triggers.length) return;

    import('../../../../scss/front/default/components/_video-scroll.scss');

    const nav = document.getElementById('main-navigation');

    /**
     * If true:
     * When a container reaches 100% once, it will stay full width forever (no more shrinking).
     * If false:
     * Keep current behavior (lock window + hysteresis).
     */
    const fixedFullWidth = true;

    // Keep full-bleed while user stays within +/- LOCK_PX from the snap point (both directions)
    const LOCK_PX = 150;

    // Hysteresis thresholds
    const FULL_ENTER = 0.85; // snap to 1 when ratio >= this
    const FULL_EXIT  = 0.65; // release full when ratio <= this (and lock is over)

    /** Clamp number between min/max */
    const clamp = (v, min, max) => Math.min(Math.max(v, min), max);

    /** Smooth thresholds */
    const thresholds = Array.from({ length: 101 }, (_, i) => i / 100);

    /** Viewport width excluding scrollbar */
    const getViewportWidth = () => document.documentElement.clientWidth;

    /** Get current fixed nav height (px) */
    const getNavHeight = () => (nav ? Math.ceil(nav.getBoundingClientRect().height) : 0);

    /** Get base container max-width */
    const getContainerBaseWidth = (container) => {
        const maxWidth = window.getComputedStyle(container).maxWidth;
        if (!maxWidth || maxWidth === 'none') return container.clientWidth;

        const parsed = parseFloat(maxWidth);
        return Number.isFinite(parsed) ? parsed : container.clientWidth;
    };

    /** Parse px value from computed style */
    const getPx = (val) => {
        const n = parseFloat(val);
        return Number.isFinite(n) ? n : 0;
    };

    /**
     * Per-container state:
     * - baseWidth, basePaddingLeft/Right: baseline metrics
     * - ratios: map(trigger -> ratio)
     * - isFull: snapped full state
     * - lockAnchorY: scrollY when entering full (lock is +/- LOCK_PX around it)
     * - isBlockedFull: if fixedFullWidth = true, once full then never shrink
     */
    const containerState = new Map();

    const ensureState = (container) => {
        if (containerState.has(container)) return containerState.get(container);

        const cs = window.getComputedStyle(container);

        const state = {
            baseWidth: getContainerBaseWidth(container),
            basePaddingLeft: getPx(cs.paddingLeft),
            basePaddingRight: getPx(cs.paddingRight),
            ratios: new Map(),
            isFull: false,
            lockAnchorY: null,
            isBlockedFull: false,
        };

        containerState.set(container, state);
        container.classList.add('is-scroll-video-container');

        return state;
    };

    /** Recompute base widths/paddings (on resize) without capturing interpolated values */
    const recomputeBaseMetrics = () => {
        containerState.forEach((state, container) => {
            container.style.maxWidth = '';
            container.style.paddingLeft = '';
            container.style.paddingRight = '';
            const cs = window.getComputedStyle(container);
            state.baseWidth = getContainerBaseWidth(container);
            state.basePaddingLeft = getPx(cs.paddingLeft);
            state.basePaddingRight = getPx(cs.paddingRight);
        });
    };

    /** Apply interpolation (max-width + padding -> 0 for full-bleed) */
    const applyContainer = (container, state, ratio) => {
        const r = clamp(ratio, 0, 1);

        const viewportWidth = getViewportWidth();
        const width = state.baseWidth + (viewportWidth - state.baseWidth) * r;

        const padL = state.basePaddingLeft * (1 - r);
        const padR = state.basePaddingRight * (1 - r);

        container.style.maxWidth = `${width}px`;
        container.style.paddingLeft = `${padL}px`;
        container.style.paddingRight = `${padR}px`;

        if (r === 0) {
            container.style.maxWidth = '';
            container.style.paddingLeft = '';
            container.style.paddingRight = '';
        }
    };

    /** Compute max ratio for a container */
    const getMaxRatio = (state) => {
        let maxRatio = 0;
        state.ratios.forEach((r) => {
            if (r > maxRatio) maxRatio = r;
        });
        return maxRatio;
    };

    /**
     * Hysteresis + bidirectional lock:
     * - Enter full when maxRatio >= FULL_ENTER, set lockAnchorY
     * - Keep full while |scrollY - lockAnchorY| <= LOCK_PX
     * - After lock window, exit full only when maxRatio <= FULL_EXIT
     *
     * + Optional permanent full lock:
     * - If fixedFullWidth === true, once fullRatio == 1, keep it forever (isBlockedFull)
     */
    const recomputeAndApply = (container) => {
        const state = ensureState(container);

        // Permanent lock: once blocked, always full
        if (fixedFullWidth && state.isBlockedFull) {
            applyContainer(container, state, 1);
            container.classList.toggle('is-scroll-video-100', true);
            return;
        }

        const y = window.scrollY;
        let maxRatio = getMaxRatio(state);

        /**
         * Fallback if IntersectionObserver ratio is not high enough but element is at top.
         * Useful if navigation height changes or observer fails to trigger exactly at 1.
         */
        const topOffset = getNavHeight();
        const rect = container.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const visibleHeight = Math.min(rect.bottom, viewportHeight) - Math.max(rect.top, topOffset);
        if (visibleHeight > 0) {
            const currentRatio = clamp(visibleHeight / (viewportHeight - topOffset), 0, 1);
            if (currentRatio > maxRatio) {
                maxRatio = currentRatio;
            }
        }

        // Enter full (snap) with tolerance
        if (!state.isFull && maxRatio >= FULL_ENTER) {
            state.isFull = true;
            state.lockAnchorY = y;

            // If option enabled, permanently block as soon as we reach "full mode"
            if (fixedFullWidth) {
                state.isBlockedFull = true;
            }
        }

        // Bidirectional lock window (works for scroll down AND scroll up)
        const isLocked =
            state.lockAnchorY !== null && Math.abs(y - state.lockAnchorY) <= LOCK_PX;

        // If lock window is over, allow release (with hysteresis)
        if (state.isFull && !isLocked && maxRatio <= FULL_EXIT) {
            // If fixedFullWidth enabled, we never reach this because isBlockedFull would be true
            state.isFull = false;
            state.lockAnchorY = null;
        }

        // If we are full but lock is over, we don't need anchor anymore (avoid “sticky” feeling)
        if (state.isFull && !isLocked && state.lockAnchorY !== null) {
            state.lockAnchorY = null;
        }

        const finalRatio = state.isFull ? 1 : maxRatio;

        applyContainer(container, state, finalRatio);
        container.classList.toggle('is-scroll-video-100', finalRatio === 1);
    };

    /** Observer (recreated on resize because rootMargin depends on nav height) */
    let observer = null;

    const createObserver = () => {
        const topOffset = getNavHeight();

        if (observer) observer.disconnect();

        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    const trigger = entry.target;
                    const container = trigger.closest('.container');
                    if (!container) return;

                    const state = ensureState(container);
                    state.ratios.set(trigger, clamp(entry.intersectionRatio, 0, 1));

                    recomputeAndApply(container);
                });
            },
            {
                threshold: thresholds,
                rootMargin: `-${topOffset}px 0px 0px 0px`,
            }
        );

        triggers.forEach((t) => observer.observe(t));
    };

    // Init
    createObserver();

    // Scroll: recompute always (ensures lock releases correctly in both directions)
    window.addEventListener(
        'scroll',
        () => {
            containerState.forEach((_, container) => {
                recomputeAndApply(container);
            });
        },
        { passive: true }
    );

    // Resize: recompute metrics + rebuild observer + re-apply
    window.addEventListener(
        'resize',
        () => {
            recomputeBaseMetrics();
            createObserver();
            containerState.forEach((_, container) => recomputeAndApply(container));
        },
        { passive: true }
    );
}