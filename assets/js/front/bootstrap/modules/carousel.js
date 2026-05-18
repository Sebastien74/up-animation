/**
 * Carousel
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

const isHome = document.body.id === 'home-body';
const isDark = document.documentElement.dataset.theme === 'dark';

if (isHome) {
    if (isDark) {
        import(/* webpackPreload: true */ '../../../../scss/front/default/components/_carousel-dark.scss');
    } else {
        import(/* webpackPreload: true */ '../../../../scss/front/default/components/_carousel-light.scss');
    }
} else if (isDark) {
    import('../../../../scss/front/default/components/_carousel-dark.scss');
} else {
    import('../../../../scss/front/default/components/_carousel-light.scss');
}

export default function () {

    const screenWidth = window.screen.width;

    let body = document.body;
    body.dataset.width = screenWidth.toString();

    let carousels = body.querySelectorAll('[data-component="carousel-bootstrap"]');
    if (carousels.length > 0) {
        import('../dist/carousel').then(({default: Carousel}) => {
            setCarousels(Carousel, carousels, true);
        }).catch(error => console.error(error.message));
    }

    /** WARNING: Can vibrate the progress bar */
    window.addEventListener("resize", function () {
        let body = document.body;
        let carousels = body.querySelectorAll('[data-component="carousel-bootstrap"]');
        let screenWidth = window.screen.width;
        if (carousels.length > 0 && (parseInt(body.dataset.width) + 10) < screenWidth) {
            import('../dist/carousel').then(({default: Carousel}) => {
                setCarousels(Carousel, carousels, false);
            }).catch(error => console.error(error.message));
        }
    });

    /** To initialize carousels */
    function setCarousels(Carousel, carousels, progress) {

        carousels.forEach(function (carousel) {

            const interval = carousel.dataset.bsInterval ? parseInt(carousel.dataset.bsInterval)
                : (carousel.dataset.interval ? parseInt(carousel.dataset.interval) : 5000);
            const autoplay = parseInt(carousel.dataset.bsAutoplay) === 1;
            const ride = parseInt(carousel.dataset.bsRide) === 0 || carousel.dataset.bsRide === 'false' ? false : (autoplay ? 'carousel' : false);
            const pause = parseInt(carousel.dataset.bsPause) === 1 ? 'hover'
                : (parseInt(carousel.dataset.bsPause) === 0 || carousel.dataset.bsPause === 'false' ? false : 'hover');
            const hasMultiplePerSlide = carousel.classList.contains('multiple-carousel');
            const items = carousel.querySelectorAll('.carousel .carousel-item');
            const activeBg = false;

            if (activeBg) {
                items.forEach(function (item) {
                    // Step 1: try to find a bg- class on the item itself
                    let bgClass = Array.from(item.classList).find(cls => cls.startsWith('bg-') && cls !== 'bg-none');
                    // Step 1b: otherwise search in descendants
                    if (!bgClass) {
                        const descendants = item.querySelectorAll('*');
                        for (let i = 0; i < descendants.length; i++) {
                            const node = descendants[i];
                            const match = Array.from(node.classList).find(cls => cls.startsWith('bg-') && cls !== 'bg-none');
                            if (match) {
                                bgClass = match;
                                break;
                            }
                        }
                    }
                    // Step 2: climb up until .layout-zone
                    if (!bgClass) {
                        let current = item.parentElement;
                        while (current && !current.classList.contains('layout-zone')) {
                            bgClass = Array.from(current.classList).find(cls => cls.startsWith('bg-') && cls !== 'bg-none');
                            if (bgClass) break;
                            current = current.parentElement;
                        }

                        // Step 2b: check .layout-zone itself
                        if (!bgClass && current && current.classList.contains('layout-zone')) {
                            bgClass = Array.from(current.classList).find(cls => cls.startsWith('bg-') && cls !== 'bg-none');
                        }
                    }
                    // Step 3: fallback
                    bgClass = bgClass || 'bg-white';
                    // Step 4: apply class
                    item.classList.add(bgClass, 'bg-dynamic');
                });
            }

            if (hasMultiplePerSlide) {

                let windowsWidth = window.innerWidth
                let inner = carousel.getElementsByClassName('carousel-inner')[0];
                let itemsCols = carousel.getElementsByClassName('item-col');
                let itemsColsCount = itemsCols.length;

                let itemPerSlide = carousel.dataset.perSlide ? parseInt(carousel.dataset.perSlide) : 4;
                itemPerSlide = itemsColsCount < itemPerSlide ? itemsColsCount : itemPerSlide;
                let breakpoints = itemsPerSlideBreakpoints(itemPerSlide, itemsCols);

                for (const key in breakpoints) {
                    let breakpoint = breakpoints[key];
                    if (windowsWidth >= breakpoint.min && windowsWidth <= breakpoint.max) {
                        itemPerSlide = breakpoint.count;
                    }
                }

                for (let j = 1; j <= 12; j++) {
                    inner.classList.remove('item-' + j);
                }
                inner.classList.add('item-' + itemPerSlide);

                items.forEach((el) => {
                    let next = el.nextElementSibling;
                    for (let j = 1; j < itemPerSlide; j++) {
                        if (!next) {
                            next = items[0];
                        }
                        let cloneChild = next.cloneNode(true);
                        el.appendChild(cloneChild.children[0]);
                        next = next.nextElementSibling;
                    }
                })
            }

            let bootstrapCarousel = new Carousel(carousel, {
                interval: interval,
                ride: ride,
                keyboard: true,
                touch: true,
                slide: autoplay, /** autoplay */
                pause: pause, /** hover or false */
            });

            /**
             * Prevent <a> navigation when clicking anywhere inside .carousel-indicators.
             * This carousel is often wrapped inside a parent link, so clicks bubble up and navigate.
             */
            carousel.addEventListener('click', (e) => {
                const indicators = e.target.closest('.carousel-indicators');
                if (!indicators) {
                    return;
                }

                const link = indicators.closest('a[href]');
                if (!link) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                const slideTo = e.target.closest('[data-bs-slide-to]');
                if (slideTo) {
                    const index = parseInt(slideTo.getAttribute('data-bs-slide-to'));
                    bootstrapCarousel.to(index);
                }

                if (typeof e.stopImmediatePropagation === 'function') {
                    e.stopImmediatePropagation();
                }
            }, true); // capture=true to intercept early

            /**
             * Prevent parent <a> navigation when clicking on carousel controls (prev/next/pause/play)
             * so arrows work even if the carousel is wrapped in a link.
             */
            carousel.addEventListener('click', (e) => {
                const control = e.target.closest('.carousel-control-prev, .carousel-control-next, .carousel-control-pause, .carousel-control-play');
                if (!control) {
                    return;
                }
                const link = control.closest('a[href]');
                if (!link) {
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                if (control.classList.contains('carousel-control-prev')) {
                    bootstrapCarousel.prev();
                } else if (control.classList.contains('carousel-control-next')) {
                    bootstrapCarousel.next();
                }
                if (typeof e.stopImmediatePropagation === 'function') {
                    e.stopImmediatePropagation();
                }
            }, true);

            // Pause Button
            carousel.querySelectorAll('.carousel-control-pause').forEach((btn) => {
                btn.addEventListener('click', () => {
                    bootstrapCarousel.pause();
                    carousel.querySelectorAll('.carousel-control-pause').forEach(b => b.classList.add('d-none'));
                    carousel.querySelectorAll('.carousel-control-play').forEach(b => b.classList.remove('d-none'));
                });
            });

            // Play Button
            carousel.querySelectorAll('.carousel-control-play').forEach((btn) => {
                btn.addEventListener('click', () => {
                    bootstrapCarousel.cycle();
                    carousel.querySelectorAll('.carousel-control-pause').forEach(b => b.classList.remove('d-none'));
                    carousel.querySelectorAll('.carousel-control-play').forEach(b => b.classList.add('d-none'));
                });
            });

            let players = carousel.querySelectorAll('.embed-youtube-play');
            for (let j = 0; j < players.length; j++) {
                let player = players[j];
                player.onclick = function () {
                    let carouselInstance = Carousel.getInstance(carousel);
                    carouselInstance.dispose();
                }
            }

            /**
             * Helpers for same-height carousels
             */

            /**
             * Run function after paint (2 RAF) to ensure the layout is committed.
             */
            function afterPaint(fn) {
                requestAnimationFrame(() => {
                    requestAnimationFrame(fn);
                });
            }

            /**
             * Run callback when all images inside the root are loaded (or already complete).
             */
            function whenImagesReady(root, cb) {
                const images = Array.from(root.querySelectorAll('img'));
                const pending = images.filter(img => !img.complete);
                if (pending.length === 0) {
                    cb();
                    return;
                }
                let left = pending.length;
                const done = () => {
                    left -= 1;
                    if (left === 0) cb();
                };
                pending.forEach((img) => {
                    img.addEventListener('load', done, {once: true});
                    img.addEventListener('error', done, {once: true});
                });
            }

            /**
             * Run callback when fonts are ready (if supported).
             */
            function whenFontsReady(cb) {
                if (document.fonts && document.fonts.ready) {
                    document.fonts.ready.then(cb).catch(cb);
                    return;
                }
                cb();
            }

            /**
             * Compute outer height (content + padding + border + vertical margins).
             */
            function outerHeight(node) {
                const rect = node.getBoundingClientRect();
                const cs = window.getComputedStyle(node);
                const mt = parseFloat(cs.marginTop) || 0;
                const mb = parseFloat(cs.marginBottom) || 0;
                return Math.ceil(rect.height + mt + mb);
            }

            /**
             * Compute the max height of carousel items without breaking Bootstrap state.
             * Measures items at the real inner width (prevents wrap differences).
             */
            function getCarouselMaxItemHeight(root) {

                const inner = root.querySelector('.carousel-inner');
                const targetWidth = inner ? inner.clientWidth : null;

                let max = 0;

                root.querySelectorAll('.carousel-item').forEach((item) => {
                    // Save inline styles to restore after measurement
                    const prev = {
                        display: item.style.display,
                        position: item.style.position,
                        visibility: item.style.visibility,
                        height: item.style.height,
                        width: item.style.width,
                        left: item.style.left,
                        top: item.style.top,
                        transform: item.style.transform,
                    };

                    // Force measurable layout without impacting flow
                    item.style.display = 'block';
                    item.style.position = 'absolute';
                    item.style.left = '0';
                    item.style.top = '0';
                    item.style.visibility = 'hidden';
                    item.style.height = 'auto';
                    item.style.transform = 'none';
                    if (targetWidth) {
                        item.style.width = `${targetWidth}px`;
                    }

                    // Measure (including margins to match visual spacing)
                    const h = outerHeight(item);
                    if (h > max) max = h;

                    // Restore styles
                    item.style.display = prev.display;
                    item.style.position = prev.position;
                    item.style.visibility = prev.visibility;
                    item.style.height = prev.height;
                    item.style.width = prev.width;
                    item.style.left = prev.left;
                    item.style.top = prev.top;
                    item.style.transform = prev.transform;
                });

                return max;
            }

            /**
             * Bind same-height behavior (no setTimeout):
             * - Wait images + fonts
             * - Measure after paint
             * - Keep in sync on resize/content changes via ResizeObserver (if available)
             */
            function bindSameHeight(root) {
                const inner = root.querySelector('.carousel-inner');
                if (!inner) return;
                const apply = () => {
                    const h = getCarouselMaxItemHeight(root);
                    inner.style.height = `${h}px`;
                };
                const init = () => afterPaint(apply);
                // Initial: wait for assets that affect layout
                whenImagesReady(root, () => {
                    whenFontsReady(init);
                });
                // Keep updated when slides change (in case the active slide has different content)
                root.addEventListener('slid.bs.carousel', () => afterPaint(apply));
                // Keep updated on resize / responsive wrap
                window.addEventListener('resize', () => afterPaint(apply));
                // Optional: observe dynamic content changes (lazyload, etc.)
                if (typeof ResizeObserver !== 'undefined') {
                    const ro = new ResizeObserver(() => afterPaint(apply));
                    ro.observe(inner);
                    root.querySelectorAll('.carousel-item').forEach(item => ro.observe(item));
                }
            }

            if (carousel.classList.contains('same-height')) {
                bindSameHeight(carousel);
            }

            /* To animate dots controls */
            const carouselIndicatorsSelectors = '.carousel-indicators [data-bs-slide-to]';
            const carouselIndicators = carousel.querySelectorAll(carouselIndicatorsSelectors);
            const carouselLoaders = carousel.querySelectorAll(`${carouselIndicatorsSelectors} span.loader`);
            const hasProgress = progress && carouselLoaders.length > 0;
            let intervalID;

            function getIndicatorIndex(indicator) {
                const button = indicator.tagName === 'SPAN' ? indicator.parentNode : indicator;
                const index = parseInt(button.dataset.index || button.closest('.loader-wrap')?.dataset.index);
                const slideTo = parseInt(button.dataset.bsSlideTo);
                return !isNaN(index) ? index : slideTo + 1;
            }

            function updateIndicators(activeIndex) {
                carousel.querySelectorAll(carouselIndicatorsSelectors).forEach(indicator => {
                    indicator.classList.toggle('active', getIndicatorIndex(indicator) === activeIndex);
                });

                if (hasProgress) {
                    carouselLoaders.forEach(loader => {
                        const idx = getIndicatorIndex(loader);
                        const property = loader.classList.contains('height') ? 'height' : 'width';
                        loader.style[property] = idx < activeIndex ? '100%' : (idx > activeIndex ? '0' : loader.style[property]);
                    });
                }
            }

            function fillCarouselIndicator(index) {
                if (!hasProgress) return;

                updateIndicators(index);
                clearInterval(intervalID);
                bootstrapCarousel.pause();

                let i = 0;
                const item = carousel.querySelectorAll('.carousel-item')[index - 1];
                const video = item?.querySelector('video');
                const startAnimation = (duration) => {
                    intervalID = setInterval(() => {
                        i++;
                        carousel.querySelectorAll(`${carouselIndicatorsSelectors}.active span.loader`).forEach(loader => {
                            if (getIndicatorIndex(loader) === index) {
                                loader.style[loader.classList.contains('height') ? 'height' : 'width'] = `${i}%`;
                            }
                        });
                        if (i >= 100) {
                            clearInterval(intervalID);
                            bootstrapCarousel.next();
                        }
                    }, duration / 100);
                };

                if (video && !video.classList.contains('loaded')) {
                    video.muted = video.loop = true;
                    video.addEventListener('loadedmetadata', () => {
                        video.dataset.duration = (video.duration * 1000).toString();
                        video.classList.add('loaded');
                        video.play().catch(e => console.error(e));
                        startAnimation(parseInt(video.dataset.duration));
                    }, {once: true});
                } else {
                    if (video) video.currentTime = 0;
                    startAnimation(video ? parseInt(video.dataset.duration) : interval);
                }
            }

            if (carouselIndicators.length > 0) {
                updateIndicators(1);
                if (hasProgress) fillCarouselIndicator(1);

                ['slide.bs.carousel', 'slid.bs.carousel'].forEach(event => {
                    carousel.addEventListener(event, (e) => {
                        const index = e.to + 1;
                        hasProgress ? fillCarouselIndicator(index) : updateIndicators(index);
                    });
                });
            }
        });
    }

    /** To get breakpoints */
    function itemsPerSlideBreakpoints(defaultCount, itemsCols) {

        const breakpoints = {
            xs: {screenSize: 320, count: 1, min: 0, max: 575},
            sm: {screenSize: 576, count: 1, min: 576, max: 767},
            md: {screenSize: 768, count: 2, min: 768, max: 991},
            lg: {screenSize: 992, count: 2, min: 992, max: 1199},
            xl: {screenSize: 1200, count: defaultCount, min: 1200, max: 1599},
            xxl: {screenSize: 1600, count: defaultCount, min: 1600, max: 5000}
        }

        let perSlidesByBreakpoint = [];
        let xsDefined = false;
        let smDefined = false;
        if (itemsCols) {
            let firstItem = itemsCols[0]
            for (const breakpoint in breakpoints) {
                let configuration = breakpoints[breakpoint]
                let count = itemsPerSlide(firstItem, 'col-' + breakpoint)
                if (breakpoint === 'xs' && count) {
                    xsDefined = true;
                } else if (breakpoint === 'sm' && count) {
                    smDefined = true;
                }
                count = count ? count : configuration.count
                perSlidesByBreakpoint.push({
                    screenSize: configuration.screenSize, count: count, min: configuration.min, max: configuration.max
                })
            }
            let xsCount = itemsPerSlide(firstItem, 'col')
            if (xsCount && !xsDefined) {
                perSlidesByBreakpoint[0].count = xsCount
            }
            if (xsCount && !smDefined) {
                perSlidesByBreakpoint[1].count = xsCount
            }
        }

        return perSlidesByBreakpoint;
    }

    /** To get items per slide */
    function itemsPerSlide(firstItem, classname) {
        for (let j = 1; j <= 12; j++) {
            if (firstItem.classList.contains(classname + '-' + j)) {
                return Math.ceil(12 / j);
            }
        }
    }
}