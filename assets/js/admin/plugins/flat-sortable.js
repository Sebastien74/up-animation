/**
 * Flat list driven by SortableJS — unified replacement for
 * jQuery UI `.sortable()` and the legacy nestable-flat wrapper.
 *
 * Markup-agnostic : works on UL/LI, OL/LI, DIV-based collections.
 *
 * @param {Element} el       Root list element.
 * @param {Object}  options  Overrides — handle, draggable,
 *                           onUpdate (called after reorder with
 *                           the SortableJS event), onStart, onEnd,
 *                           plus any SortableJS option.
 * @returns {Promise<Sortable>}
 */
export default function flatSortable(el, options = {}) {

    if (!el) {
        return Promise.resolve(null);
    }

    return import('sortablejs').then(({default: Sortable}) => {

        const defaults = {
            animation: 200,
            easing: "cubic-bezier(0.22, 1, 0.36, 1)",
            ghostClass: "sortable-placeholder",
            dragClass: "sortable-drag",
            chosenClass: "sortable-chosen",
            forceFallback: true,
            fallbackTolerance: 5,
            swapThreshold: 0.65,
            scroll: true,
            bubbleScroll: true,
            scrollSensitivity: 80,
            scrollSpeed: 12,
        };

        const baseOnStart = options.onStart;
        const baseOnEnd = options.onEnd;

        const merged = Object.assign({}, defaults, options, {
            onStart: function (evt) {
                document.body.classList.add('sorting-active');
                if (typeof baseOnStart === 'function') {
                    baseOnStart(evt);
                }
            },
            onEnd: function (evt) {
                document.body.classList.remove('sorting-active');
                if (typeof baseOnEnd === 'function') {
                    baseOnEnd(evt);
                }
            },
        });

        return Sortable.create(el, merged);
    });
}
