import {isInViewport} from "../functions"

/**
 * Medias
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @doc https://masonry.desandro.com
 */
export default function (blocksMedias) {
    // Prefer IntersectionObserver to avoid per-element scroll listeners
    const canObserve = 'IntersectionObserver' in window;
    let observer = null;

    if (canObserve) {
        observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const block = entry.target;
                const picture = block.querySelector('picture');
                if (!picture) return;
                const img = picture.querySelector('img');
                if (!img) return;
                if (!img.classList.contains('in-viewport')) {
                    img.classList.add('in-viewport');
                }
                observer.unobserve(block);
            });
        }, {
            root: null,
            rootMargin: '300px 0px',
            threshold: 0.01
        });
    }

    // Fallback: one rAF-throttled scroll handler for all blocks
    let scheduled = false;
    const onScrollFallback = () => {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(() => {
            blocksMedias.forEach(block => {
                const picture = block.querySelector('picture');
                if (!picture) return;
                const img = picture.querySelector('img');
                if (!img) return;
                if (isInViewport(block, 300) && !img.classList.contains('in-viewport')) {
                    img.classList.add('in-viewport');
                }
            });
            scheduled = false;
        });
    };

    blocksMedias.forEach(block => {
        const picture = block.querySelector('picture');
        if (!picture) return;
        const img = picture.querySelector('img');
        if (!img) return;

        // Initial check for above-the-fold
        if (isInViewport(block, 300) && !img.classList.contains('in-viewport')) {
            img.classList.add('in-viewport');
        }

        if (observer) {
            observer.observe(block);
        }
    });

    if (!observer) {
        window.addEventListener('scroll', onScrollFallback, { passive: true });
    }
}