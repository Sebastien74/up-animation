import '../../../../scss/front/default/components/_infinite-marquee.scss';

/**
 * Marquee.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (els) {
    els.forEach((marquee) => {
        const content = marquee.querySelector('.marquee-content');
        const inner = marquee.querySelector('.marquee-inner');
        if (!content || !inner) return;
        lazyLoadImages(content).then(() => {
            inner.querySelectorAll('[aria-hidden="true"]').forEach(clone => clone.remove());
            const clone = content.cloneNode(true);
            clone.setAttribute('aria-hidden', 'true');
            inner.appendChild(clone);
            requestAnimationFrame(() => {
                const width = content.scrollWidth;
                inner.style.width = `${width * 2}px`;
                const speed = parseFloat(marquee.dataset.speed || '20');
                const animName = `scroll-${Math.random().toString(36).slice(2, 8)}`;
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes ${animName} {
                        0% { transform: translateX(0); }
                        100% { transform: translateX(-${width}px); }
                    }
                `;
                document.head.appendChild(style);
                inner.style.animation = `${animName} ${speed}s linear infinite`;
            });
        });
    });
}

function lazyLoadImages(container) {

    return new Promise((resolve) => {

        const lazySources = container.querySelectorAll('source[data-srcset]');
        const lazyImages = container.querySelectorAll('img[data-src], img[data-srcset], img[data-sizes]');

        const totalImages = lazyImages.length;
        let loadedCount = 0;

        lazySources.forEach(source => {
            const srcset = source.getAttribute('data-srcset');
            if (srcset) source.setAttribute('srcset', srcset);
        });

        lazyImages.forEach(img => {
            const src = img.getAttribute('data-src');
            const srcset = img.getAttribute('data-srcset');
            const sizes = img.getAttribute('data-sizes');
            if (src) img.setAttribute('src', src);
            if (srcset) img.setAttribute('srcset', srcset);
            if (sizes) img.setAttribute('sizes', sizes);
            if (img.complete && img.naturalWidth !== 0) {
                loadedCount++;
            } else {
                img.addEventListener('load', () => {
                    loadedCount++;
                    if (loadedCount === totalImages) {
                        resolve();
                    }
                });
            }
        });

        if (totalImages === 0 || loadedCount === totalImages) {
            resolve();
        }
    });
}