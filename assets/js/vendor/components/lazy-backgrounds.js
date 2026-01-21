/**
 * Lazy loading background with preload
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (backgrounds, styles) {

    const setBackgrounds = function (backgrounds) {

        const init = function (backgrounds) {

            const height = window.innerHeight;
            const width = window.innerWidth;
            const orientation = height > width ? 'portrait' : 'landscape';
            let screenType = width > 991 ? 'desktop' : 'tablet';
            if (width < 768) {
                screenType = 'mobile';
            }

            backgrounds.forEach(function (el) {

                if (isInViewport(el, 100)) {

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

                    background = orientation === 'landscape' && typeof desktopBackground !== 'undefined' ? desktopBackground : background;
                    if (onlySmallScreen && screenType === 'desktop') {
                        return;
                    }

                    // Apply background style
                    el.style.cssText = background;
                    const isInFirstZone = el.closest('.layout-zone.position-1');

                    // Preload if not already handled
                    if (isInFirstZone && !el.dataset.preloadInserted && background.includes('url(')) {
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
                }
            });
        };

        init(backgrounds);
        window.addEventListener("resize", function () {
            init(backgrounds);
        });
        window.addEventListener("scroll", function () {
            init(backgrounds);
        });
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
        setBackgrounds(styles);
    }

    if (backgrounds.length > 0) {
        setBackgrounds(backgrounds);
    }

    function isInViewport(el, offset = 0) {
        const bounding = el.getBoundingClientRect();
        const myElementHeight = el.offsetHeight;
        const myElementWidth = el.offsetWidth;
        return bounding.top >= -myElementHeight
            && bounding.left >= -myElementWidth
            && bounding.right <= (window.innerWidth + offset || document.documentElement.clientWidth + offset) + myElementWidth
            && bounding.bottom <= (window.innerHeight || document.documentElement.clientHeight) + myElementHeight;
    }
}