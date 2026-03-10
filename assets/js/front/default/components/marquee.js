export default function (els) {

    if (els.length > 0) {
        import('../../../../scss/front/default/components/_infinite-marquee.scss');
    }

    els.forEach((marquee) => {
        const content = marquee.querySelector('.marquee-content');
        const inner = marquee.querySelector('.marquee-inner');
        if (!content || !inner) return;

        let animationName = null;

        let isInitialized = false;

        const initMarquee = () => {
            if (isInitialized) return;
            isInitialized = true;

            const contentWidth = content.offsetWidth;
            const containerWidth = marquee.offsetWidth;

            if (contentWidth <= 0 || containerWidth <= 0) {
                isInitialized = false;
                // On réessaie un peu plus tard si les dimensions ne sont pas encore disponibles
                setTimeout(initMarquee, 100);
                return;
            }

            requestAnimationFrame(() => {
                // Nettoyage des clones précédents et de l'animation
                inner.querySelectorAll('[aria-hidden="true"]').forEach(clone => clone.remove());
                inner.style.animation = '';
                inner.style.width = '';
                if (animationName) {
                    const oldStyle = document.getElementById(animationName);
                    if (oldStyle) oldStyle.remove();
                }

                // Nombre de clones nécessaires pour couvrir au moins 2x la largeur du conteneur
                // pour assurer une transition fluide
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

                isInitialized = false;
            });
        };

        lazyLoadImages(content).then(() => {
            initMarquee(); // Initialisation immédiate après le chargement des images
            
            // Utiliser ResizeObserver pour recalculer si les dimensions changent (responsive)
            if (window.ResizeObserver) {
                const resizeObserver = new ResizeObserver(() => {
                    initMarquee();
                });
                resizeObserver.observe(marquee);
            } else {
                window.addEventListener('resize', initMarquee);
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