export default function (els) {

    if (els.length > 0) {
        import('../../../../scss/front/default/components/_infinite-marquee.scss');
    }

    els.forEach((marquee) => {
        const content = marquee.querySelector('.marquee-content');
        const inner = marquee.querySelector('.marquee-inner');
        if (!content || !inner) return;

        let animationName = null;
        let resizeTimer = null;
        let pendingRaf = null;
        let retryTimer = null;

        const cleanup = () => {
            inner.querySelectorAll('[aria-hidden="true"]').forEach(clone => clone.remove());
            inner.style.animation = 'none';
            inner.style.width = '';
            if (animationName) {
                const oldStyle = document.getElementById(animationName);
                if (oldStyle) oldStyle.remove();
                animationName = null;
            }
        };

        const initMarquee = () => {
            if (pendingRaf !== null) {
                cancelAnimationFrame(pendingRaf);
                pendingRaf = null;
            }
            if (retryTimer !== null) {
                clearTimeout(retryTimer);
                retryTimer = null;
            }

            cleanup();

            const contentWidth = content.getBoundingClientRect().width;
            const containerWidth = marquee.getBoundingClientRect().width;

            if (contentWidth <= 0 || containerWidth <= 0) {
                retryTimer = setTimeout(initMarquee, 100);
                return;
            }

            pendingRaf = requestAnimationFrame(() => {
                pendingRaf = null;

                // Nombre de clones nécessaires pour couvrir au moins 2× la largeur du conteneur
                // afin d'assurer une boucle visuelle continue.
                const repeatCount = Math.max(1, Math.ceil((containerWidth * 2) / contentWidth));

                for (let i = 0; i < repeatCount; i++) {
                    const clone = content.cloneNode(true);
                    clone.setAttribute('aria-hidden', 'true');
                    inner.appendChild(clone);
                }

                inner.style.width = `${contentWidth * (repeatCount + 1)}px`;

                const speed = parseFloat(marquee.dataset.speed || '20');
                animationName = `scroll-${Math.random().toString(36).slice(2, 8)}`;
                const style = document.createElement('style');
                style.id = animationName;

                const rawNonce = document.documentElement.getAttribute('data-nonce');
                const nonce = rawNonce && rawNonce.startsWith('nonce-') ? rawNonce.slice(6) : rawNonce;
                if (nonce) {
                    // CSP expects the HTML attribute 'nonce' to be the raw value (without the 'nonce-' prefix)
                    style.setAttribute('nonce', nonce);
                }

                style.textContent = `
                    @keyframes ${animationName} {
                        0% { transform: translateX(0); }
                        100% { transform: translateX(-${contentWidth}px); }
                    }
                `;
                document.head.appendChild(style);
                inner.style.animation = `${animationName} ${speed}s linear infinite`;
            });
        };

        const scheduleInit = () => {
            if (resizeTimer !== null) clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                resizeTimer = null;
                initMarquee();
            }, 150);
        };

        lazyLoadImages(content).then(() => {
            // Retire les items dont l'image n'a pas pu être affichée (404, srcset invalide, format KO…)
            // pour éviter les « trous » de 120 px dans le défilement.
            content.querySelectorAll('.marquee-item').forEach(item => {
                const img = item.querySelector('img');
                if (!img || !img.complete || img.naturalWidth === 0) {
                    item.remove();
                }
            });

            initMarquee();

            if (window.ResizeObserver) {
                const resizeObserver = new ResizeObserver(scheduleInit);
                resizeObserver.observe(marquee);
            } else {
                window.addEventListener('resize', scheduleInit);
            }
        });
    });
}

function lazyLoadImages(container) {

    return new Promise((resolve) => {

        const lazySources = container.querySelectorAll('source[data-srcset]');
        const lazyImages = container.querySelectorAll('img[data-src], img[data-srcset], img[data-sizes]');

        const totalImages = lazyImages.length;
        let loadedCount = 0;

        const checkDone = () => {
            if (loadedCount >= totalImages) resolve();
        };

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
                const onSettle = () => {
                    img.removeEventListener('load', onSettle);
                    img.removeEventListener('error', onSettle);
                    loadedCount++;
                    checkDone();
                };
                img.addEventListener('load', onSettle);
                img.addEventListener('error', onSettle);
            }
        });

        checkDone();
    });
}
