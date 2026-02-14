/**
 * Lazy load
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let body = document.body;
    let skinAdmin = body.classList.contains('skin-admin');

    import(/* webpackPreload: true */ 'lazysizes').then(({default: lazySizes}) => {
        lazySizes.cfg.lazyClass = 'lazy-load';
        lazySizes.loadMode = 1;
        lazySizes.preloadClass = 'lazy-preload';
        /** On lazy images loaded */
        document.addEventListener("lazyloaded", function (e) {
            let target = e.target;
            let parent = target.parentNode;
            parent.classList.add('picture-loaded');
            parent.classList.remove('loading');
        }, false);
    }).catch(error => console.error(error.message));

    /** To set grow flex wrap to svg img **/
    if (!skinAdmin) {
        const batchGrow = [];
        document.querySelectorAll('img.img-svg').forEach(function (svg) {
            let block = svg.closest('.layout-block');
            if (block && (block.classList.contains('justify-content-start')
                || block.classList.contains('justify-content-center')
                || block.classList.contains('justify-content-end'))) {
                let blockWrap = block.querySelector('.layout-block-content');
                if (blockWrap) {
                    batchGrow.push(blockWrap);
                }
            }
        });
        batchGrow.forEach(el => el.classList.add('flex-grow'));

        const batchRadius = [];
        document.querySelectorAll('img').forEach(function (image) {
            if (image.classList.contains('radius')) {
                let hoverContainer = image.closest('.img-hover-buttons-wrap');
                if (hoverContainer) {
                    batchRadius.push(hoverContainer);
                }
            }
        });
        batchRadius.forEach(el => el.classList.add('radius'));
    }

    /** Medias loader */
    let hx = document.querySelector('hx\\:include');
    if (hx) {
        import('../../vendor/components/medias-loader').then(({default: mediaLoader}) => {
            new mediaLoader();
        }).catch(error => console.error(error.message));
    }

    /** Images loader generating */
    const imageLoaders = document.querySelectorAll('.img-loader-wrap.generating');
    if (imageLoaders.length > 0) {
        const loaderData = [];
        imageLoaders.forEach((wrap) => {
            let image = wrap.querySelector('img');
            if (image) {
                loaderData.push({
                    image: image,
                    width: image.offsetWidth,
                    height: image.offsetHeight
                });
            }
        });
        loaderData.forEach(item => {
            if (item.width) item.image.setAttribute('width', item.width.toString());
            if (item.height) item.image.setAttribute('height', item.height.toString());
        });
    }

    /** Lazy loading background */
    let backgrounds = document.querySelectorAll("*[data-background]");
    let styles = document.querySelectorAll("*[data-style]");
    if (backgrounds.length > 0 || styles.length > 0) {
        import('./lazy-backgrounds').then(({default: lazyBackgrounds}) => {
            new lazyBackgrounds(backgrounds, styles);
        }).catch(error => console.error(error.message));
    }

    /** Lazy loading videos */
    let videosYoutube = document.querySelectorAll('.embed-youtube')
    let videosEl = document.querySelectorAll(".lazy-video")
    if (videosYoutube.length > 0 || videosEl.length > 0) {
        import('./lazy-videos').then(({lazyVideos: LazyVideos}) => {
            new LazyVideos(videosYoutube, videosEl);
        }).catch(error => console.error(error.message));
    }

    /** Videos not lazy */
    const nonLazyVideos = document.querySelectorAll("video:not(.lazy-video)");
    if (nonLazyVideos.length > 0) {
        nonLazyVideos.forEach(function (video) {
            let hideElementSelector = video.dataset.hideEnded;
            let hideElement = hideElementSelector ? document.querySelector(hideElementSelector) : null;
            video.onended = function () {
                video.classList.add('ended');
                if (hideElement) {
                    hideElement.classList.add('completed');
                }
                body.classList.remove('overflow-hidden');
            };
        });
    }

    /** Generated files */
    document.querySelectorAll('.spinner-wrap.as-placeholder').forEach(el => {
        el.classList.add('d-none')
    });

    /** Larges files */
    const largeFileContainers = document.querySelectorAll('.large-file-container');
    if (largeFileContainers.length > 0) {
        largeFileContainers.forEach(el => {
            const parent = el.parentNode;
            if (parent) {
                parent.style.setProperty('position', 'relative', 'important');
                parent.style.setProperty('display', 'inline-block', 'important');
                parent.style.setProperty('width', '100%', 'important');
            }
        });
    }
}