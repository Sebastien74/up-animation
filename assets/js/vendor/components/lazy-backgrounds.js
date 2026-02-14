/**
 * Lazy loading background with preload
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (backgrounds, styles) {

    const height = window.innerHeight;
    const width = window.innerWidth;
    const orientation = height > width ? 'portrait' : 'landscape';
    let screenType = width > 991 ? 'desktop' : 'tablet';
    if (width < 768) {
        screenType = 'mobile';
    }

    const applyBackground = function (el) {
        let background = el.dataset.background;
        const desktopBackground = el.dataset.desktopBackground;
        const tabletBackground = el.dataset.tabletBackground;
        const mobileBackground = el.dataset.mobileBackground;
        const onlySmallScreen = el.classList.contains('bg-only-small');

        if (orientation === 'portrait') {
            if (screenType === 'mobile' && typeof mobileBackground !== 'undefined') {
                background = mobileBackground;
            } else if (screenType === 'mobile' && typeof tabletBackground !== 'undefined') {
                background = tabletBackground;
            } else if (screenType === 'tablet' && typeof tabletBackground !== 'undefined') {
                background = tabletBackground;
            } else if (screenType === 'tablet' && typeof mobileBackground !== 'undefined') {
                background = mobileBackground;
            }
        }

        if (orientation === 'landscape' && typeof desktopBackground !== 'undefined') {
            background = desktopBackground;
        }

        if (onlySmallScreen && screenType === 'desktop') {
            return;
        }

        // Apply background style
        if (background && el.style.cssText !== background) {
            el.style.cssText = background;
        }

        const isInFirstZone = el.closest('.layout-zone.position-1');

        // Preload if not already handled
        if (isInFirstZone && !el.dataset.preloadInserted && background && background.includes('url(')) {
            const urlMatch = background.match(/url\(["']?(.*?)["']?\)/);
            if (urlMatch && urlMatch[1]) {
                const imageUrl = urlMatch[1];

                // Inject preload <link> into <head> if not already present
                if (!document.querySelector(`link[rel="preload"][href="${imageUrl}"]`)) {
                    const preloadLink = document.createElement('link');
                    preloadLink.rel = 'preload';
                    preloadLink.as = 'image';
                    preloadLink.href = imageUrl;
                    preloadLink.fetchPriority = 'high';
                    document.head.appendChild(preloadLink);
                }

                // Optional hidden image (fallback for older browsers)
                const preloadImg = document.createElement('img');
                preloadImg.src = imageUrl;
                preloadImg.loading = 'eager';
                preloadImg.fetchPriority = 'high';
                preloadImg.decoding = 'async';
                preloadImg.style.display = 'none';
                document.body.appendChild(preloadImg);

                el.dataset.preloadInserted = 'true';
            }
        }
    };

    const processElements = function (elements) {
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        applyBackground(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, {rootMargin: '200px'});

            elements.forEach(el => observer.observe(el));
        } else {
            // Fallback for browsers without IntersectionObserver
            elements.forEach(el => applyBackground(el));
        }
    };

    styles.forEach(function (tag) {
        const styleDecode = JSON.parse(tag.dataset.style);
        styleDecode.forEach(function (style) {
            if (style.screen === 'desktop') {
                tag.dataset.background = style.style;
            } else if (style.screen === 'tablet') {
                tag.dataset.tabletBackground = style.style;
            } else if (style.screen === 'mobile') {
                tag.dataset.mobileBackground = style.style;
            }
        });
    });

    if (styles.length > 0) {
        processElements(styles);
    }

    if (backgrounds.length > 0) {
        processElements(backgrounds);
    }
}