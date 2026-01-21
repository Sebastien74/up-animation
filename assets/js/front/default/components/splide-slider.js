import {Counter} from './modules/splide-counter';
import {isInViewport} from "../functions";
import '../../../../scss/vendor/components/_splide.scss';

/**
 * Splide Sliders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @doc https://splidejs.com/
 */
export default function (sliders) {

    import('@splidejs/splide').then(({Splide: Splide}) => {

        let imgSizes = function (slider) {
            slider.querySelector('.splide__list').classList.add('d-flex');
            slider.querySelectorAll('.splide__slide').forEach(function (slide) {
                slide.querySelectorAll('picture').forEach(function (picture) {
                    const hoverCard = picture.closest('.hover-card');
                    if (!hoverCard) {
                        picture.style.width = picture.clientWidth + 'px';
                    }
                });
            });
        }

        let init = function (slider, useClones = false) {

            if (!slider.classList.contains('thumbnails-slider')) {

                let screenWidth = window.screen.width;

                let isMobile = screenWidth <= 767;
                let isTablet = screenWidth > 767 && screenWidth <= 991;
                let isMiniPc = screenWidth > 991 && screenWidth <= 1199;
                let isMediumPc = screenWidth > 1199 && screenWidth <= 1399;

                let activeSlider = (!slider.classList.contains('not-mobile') && !slider.classList.contains('not-desktop') && !slider.classList.contains('max-tablet'))
                    || slider.classList.contains('not-desktop') && screenWidth <= 767
                    || slider.classList.contains('not-mobile') && screenWidth > 767
                    || slider.classList.contains('max-tablet') && screenWidth <= 991;

                if (activeSlider) {

                    let items = slider.querySelectorAll('.splide__slide');
                    let itemsCount = items.length;
                    let promoteFirst = slider.dataset.promoteFirst ? parseInt(slider.dataset.promoteFirst) === 1 : false;
                    let perPage = slider.dataset.items ? parseInt(slider.dataset.items) : 1;
                    let perPageMiniPc = slider.dataset.itemsMiniPc ? parseInt(slider.dataset.itemsMiniPc) : 2;
                    let perPageMediumPc = slider.dataset.itemsMediumPc ? parseInt(slider.dataset.itemsMediumPc) : 2;
                    let perPageTablet = slider.dataset.itemsTablet ? parseInt(slider.dataset.itemsTablet) : 2;
                    let perPageMobile = slider.dataset.itemsMobile ? parseInt(slider.dataset.itemsMobile) : 1;
                    let perMove = slider.dataset.perMove ? parseInt(slider.dataset.perMove) : 1;
                    let itemsLength = slider.dataset.length ? slider.dataset.length : perPage;
                    let autoplay = slider.dataset.autoplay ? parseInt(slider.dataset.autoplay) === 1 : false;
                    let pauseOnHover = slider.dataset.pause ? parseInt(slider.dataset.pause) === 1 : true;
                    let drag = slider.dataset.drag ? parseInt(slider.dataset.drag) === 1 : true;
                    let pagination = slider.dataset.dots ? parseInt(slider.dataset.dots) === 1 : false;
                    let interval = slider.dataset.interval ? parseInt(slider.dataset.interval) : 2000;
                    let lazyLoad = slider.dataset.lazyLoad && parseInt(slider.dataset.lazyLoad) === 0 ? false : (slider.dataset.lazyLoad ? slider.dataset.lazyLoad : 'nearby');
                    let focus = slider.dataset.focus ? slider.dataset.focus : 'left'
                    let focusMobile = slider.dataset.focusMobile ? slider.dataset.focusMobile : focus;
                    let focusTablet = slider.dataset.focusTablet ? slider.dataset.focusTablet : focusMobile;
                    let focusMediumPc = slider.dataset.focusMediumPc ? slider.dataset.focusMediumPc : focusTablet;
                    let focusMiniPc = slider.dataset.focusMiniPc ? slider.dataset.focusMiniPc : focusMediumPc;
                    let gap = slider.dataset.gap ? slider.dataset.gap : '0rem';
                    let gapMobile = slider.dataset.gapMobile ? slider.dataset.gapMobile : focus;
                    let gapTablet = slider.dataset.gapTablet ? slider.dataset.gapTablet : gapMobile;
                    let gapMediumPc = slider.dataset.gapMediumPc ? slider.dataset.gapMediumPc : gapTablet;
                    let gapMiniPc = slider.dataset.gapMiniPc ? slider.dataset.gapMiniPc : gapMediumPc;
                    let initWidth = slider.dataset.width ? parseInt(slider.dataset.offsetMobile) : 1;
                    let offset = slider.dataset.offset ? parseInt(slider.dataset.offset) : 0;
                    let offsetMobile = slider.dataset.offsetMobile ? parseInt(slider.dataset.offsetMobile) : 0;
                    let offsetTablet = slider.dataset.offsetTablet ? parseInt(slider.dataset.offsetTablet) : offsetMobile;
                    let offsetMediumPc = slider.dataset.offsetMediumPc ? parseInt(slider.dataset.offsetMediumPc) : offsetTablet;
                    let offsetMiniPc = slider.dataset.offsetMiniPc ? parseInt(slider.dataset.offsetMiniPc) : offsetMediumPc;
                    let counter = slider.dataset.counter ? parseInt(slider.dataset.counter) === 1 : false;
                    let btnPrevIdentifier = slider.dataset.btnPrev ? slider.dataset.btnPrev : '.btn-prev';
                    let btnNextIdentifier = slider.dataset.btnNext ? slider.dataset.btnNext : '.btn-next';
                    let progressBar = slider.querySelector('.slider-progress');

                    const fade = slider.dataset.fade ? parseInt(slider.dataset.fade) === 1 : false;
                    const asFade = perPage === 1 && fade;

                    if (isMobile) {
                        itemsLength = perPageMobile;
                        offset = offsetMobile;
                        focus = focusMobile;
                        gap = gapMobile;
                    } else if (isTablet) {
                        itemsLength = perPageTablet;
                        offset = offsetTablet;
                        focus = focusTablet;
                        gap = gapTablet;
                    } else if (isMediumPc) {
                        itemsLength = perPageMediumPc;
                        offset = offsetMediumPc;
                        focus = focusMediumPc;
                        gap = gapMediumPc;
                    } else if (isMiniPc) {
                        itemsLength = perPageMiniPc;
                        offset = offsetMiniPc;
                        focus = focusMiniPc;
                        gap = gapMiniPc;
                    }

                    if (itemsCount <= itemsLength) {
                        pagination = false;
                    }

                    items.forEach(function (slide, j) {
                        slide.classList.remove('col-lg');
                        slide.classList.remove('col');
                        slide.classList.remove('d-none');
                        slide.classList.add('index-' + j);
                        slide.setAttribute('data-index', j);
                    });

                    let clones = isMobile && itemsLength > 1 ? 3 : (isMobile && perPageMobile === 1 ? 1 : perPage);
                    if (!useClones || (itemsCount <= perPage)) {
                        clones = 0;
                    }

                    let type = 'slide';
                    if (items.length <= perPage && perPage !== 1) {
                        type = 'slide';
                    } else if (items.length > 1 && perPage !== 1) {
                        type = 'loop';
                    }

                    if (asFade) {
                        type = 'fade';
                    } else if (clones) {
                        type = 'loop';
                    }

                    const configBase = {
                        type: type,
                        trimSpace: true,
                        focus: focus,
                        perPage: perPage,
                        perMove: perMove,
                        flickPower: 1,
                        flickMaxPages: 1,
                        autoHeight: false,
                        gap: gap,
                        autoplay: autoplay,
                        clones: clones,
                        pauseOnHover: pauseOnHover,
                        drag: drag && items.length > 1,
                        pagination: pagination,
                        keyboard: false,
                        rewind: true,
                        interval: interval,
                        lazyLoad: lazyLoad,
                    };

                    function getConfig() {
                        let perPageScreen;
                        if (window.innerWidth >= 1400) {
                            perPageScreen = perPage;
                        } else if (window.innerWidth >= 1200 && window.innerWidth < 1300) {
                            perPageScreen = perPageMediumPc;
                        } else if (window.innerWidth >= 992 && window.innerWidth < 1200) {
                            perPageScreen = perPageMiniPc;
                        } else if (window.innerWidth >= 768 && window.innerWidth < 992) {
                            perPageScreen = perPageTablet;
                        } else {
                            perPageScreen = perPageMobile;
                        }
                        let arrows = slider.dataset.arrows ? parseInt(slider.dataset.arrows) === 1 : items.length >= perPageScreen;
                        if (items.length <= perPageScreen && screenWidth > 767 || items.length <= 1) {
                            arrows = false;
                        }
                        if (itemsCount < perPageScreen) {
                            arrows = false;
                        }
                        if (!arrows) {
                            slider.querySelectorAll('.arrows-wrap').forEach((wrap) => {
                                wrap.classList.add('d-none');
                            });
                        }
                        const sliderWidth = slider.clientWidth - offset;
                        const slideWidth = Math.round(sliderWidth / perPageScreen);
                        return {
                            ...configBase,
                            arrows: arrows,
                            perPage: perPageScreen,
                            fixedWidth: (promoteFirst && screenWidth > 767) || !initWidth ? false : slideWidth,
                            autoWidth: promoteFirst && screenWidth > 767,
                        };
                    }

                    const config = getConfig();
                    let splide = new Splide(slider, config);

                    if (!config.arrows || itemsCount <= config.perPage) {
                        slider.querySelectorAll('.arrows-wrap').forEach((wrap) => {
                            wrap.classList.add('disabled');
                        });
                    }

                    // window.addEventListener('resize', () => {
                    //     splide.destroy();
                    //     splide = new Splide(slider, getConfig());
                    //     splide.mount();
                    // });

                    splide.on('mounted', function () {
                        let track = slider.querySelector('.splide__track');
                        let list = slider.querySelector('.splide__list');
                        slider.removeAttribute('role');
                        list.setAttribute('role', 'list');
                        slider.querySelectorAll('.splide__slide').forEach(function (slide) {
                            slide.setAttribute('role', 'listitem');
                            slide.classList.add('loaded');
                            slide.addEventListener("mouseenter", function () {
                                if (track) {
                                    track.classList.add('move');
                                }
                            }, false)
                            let videos = slide.querySelectorAll('video');
                            if (videos.length > 0) {
                                import('../../../vendor/components/lazy-videos').then(({lazyVideos: LazyVideos}) => {
                                    new LazyVideos();
                                });
                            }
                            if (isMobile && itemsCount <= 1 && slide.classList.contains('splide__slide--clone')) {
                                slide.remove();
                            }
                        });
                    });

                    splide.on('ready', function () {
                        const activeSlide = slider.querySelector('.splide__slide.is-active.is-visible');
                        if (activeSlide) {
                            activeSlide.classList.add('is-active-on-move');
                        }
                        playVideo(slider);
                        window.addEventListener('scroll', function () {
                            playVideo(slider);
                        });
                        // /** If slider is offset and there's only one image */
                        // if ('left' === focus) {
                        //     setTimeout(() => {
                        //         let list = slider.querySelector('.splide__list');
                        //         list.style.transform = 'initial';
                        //     }, 200);
                        // }
                    });

                    /** Uncomment if promote first not working **/
                    // let patchPromoteFirst = function (splide) {
                    //     splide.on('mounted', function () {
                    //         if (perPage > 1) {
                    //             let previousPluginEl = slider.getElementsByClassName('splide__arrow--prev');
                    //             let previousPluginBtn = previousPluginEl.length > 0 ? previousPluginEl[0] : null;
                    //             let nextPluginEl = slider.getElementsByClassName('splide__arrow--next');
                    //             let nextPluginBtn = nextPluginEl.length > 0 ? nextPluginEl[0] : null;
                    //             if (previousPluginBtn) {
                    //                 previousPluginBtn.click();
                    //             }
                    //             if (nextPluginBtn) {
                    //                 setTimeout(function () {
                    //                     nextPluginBtn.click();
                    //                 }, 500);
                    //             }
                    //         }
                    //     })
                    // };
                    // patchPromoteFirst(splide);

                    splide.on('moved', function () {
                        playVideo(slider);
                    });

                    splide.on('move', function (newIndex) {
                        const slideComponent = splide.Components.Slides.getAt(newIndex);
                        const el = slideComponent.slide;
                        document.querySelectorAll('.splide__slide.is-active-on-move').forEach(function (slide) {
                            slide.classList.remove('is-active-on-move');
                        });
                        el.classList.add('is-active-on-move');
                        if (!slider.classList.contains('clones-loaded')) {
                            slider.classList.add('clones-loaded');
                            imgSizes(slider);
                            splide.destroy(true);
                            slider.setAttribute('style', 'opacity: 0;');
                            setTimeout(() => {
                                let reinitSplide = init(slider, true);
                                slider.querySelector('.splide__list').classList.remove('d-flex');
                                slider.setAttribute('style', 'opacity: 1;');
                                reinitSplide.go(newIndex);
                            }, 50);
                        }
                    });

                    if (!config.arrows && config.autoplay && !slider.classList.contains('clones-loaded')) {
                        slider.classList.add('clones-loaded');
                        init(slider, true);
                    }

                    /** To go to center focus image if perPage === items numbers */
                    splide.on('mounted', function () {
                        if (items.length === perPage && focus === 'center') {
                            let centerIndex = Math.floor(items.length / 2);
                            splide.go(centerIndex);
                        } else if (focus === 'center' && config.perPage === 2) {
                            splide.go(2);
                        }
                    });

                    splide.on('mounted move', function () {
                        if (progressBar) {
                            let progress = progressBar.querySelector('.slider-progress-bar');
                            let end = splide.Components.Controller.getEnd() + 1;
                            let rate = Math.min((splide.index + 1) / end, 1);
                            progress.style.width = String(100 * rate) + '%';
                        }
                    });

                    /** To set options */
                    let options = {}
                    if (counter) {
                        Object.assign(options, {'Counter': Counter});
                    }

                    const haveThumbs = slider.classList.contains('with-thumbnails');
                    const thumbsSlider = haveThumbs ? slider.closest('.splide-container').querySelector('.thumbnails-slider') : false;

                    if (thumbsSlider) {
                        let thumbnails = new Splide(thumbsSlider, {
                            rewind: true,
                            fixedWidth: 80,
                            fixedHeight: 80,
                            isNavigation: true,
                            gap: 10,
                            focus: 'left',
                            pagination: false,
                            arrows: false,
                            cover: true,
                            dragMinThreshold: {
                                mouse: 4,
                                touch: 10,
                            },
                            breakpoints: {
                                640: {
                                    fixedWidth: 80,
                                    fixedHeight: 80,
                                },
                            },
                        });
                        splide.sync(thumbnails);
                        splide.mount(options);
                        thumbnails.mount();
                    } else {
                        splide.mount(options);
                    }

                    /** Custom arrows */
                    let customArrows = function (slider, type, identifier = null) {

                        const customArrow = function (btn) {
                            let pluginEl = slider.getElementsByClassName('splide__arrow--' + type)
                            let pluginBtn = pluginEl.length > 0 ? pluginEl[0] : null;
                            if (pluginBtn) {
                                pluginBtn.classList.add('d-none');
                            }
                            // if (btn && !arrows) {
                            //     btn.classList.add('d-none');
                            // }
                            btn.onclick = function (event) {
                                event.preventDefault();
                                if (pluginBtn) {
                                    const direction = type === 'next' ? '+1' : '-1';
                                    if (!slider.classList.contains('clones-loaded')) {
                                        slider.classList.add('clones-loaded');
                                        imgSizes(slider);
                                        splide.destroy(true);
                                        setTimeout(() => {
                                            init(slider, true);
                                        }, 25);
                                        setTimeout(() => {
                                            btn.click();
                                        }, 25);
                                    } else {
                                        splide.go(direction);
                                    }
                                }
                            }
                        }

                        if (identifier) {
                            if (identifier.includes("#")) {
                                const button = document.querySelector(identifier);
                                if (button) {
                                    customArrow(button);
                                }
                            } else {
                                slider.parentNode.querySelectorAll(identifier).forEach(function (btn) {
                                    customArrow(btn);
                                });
                            }
                        }
                    };

                    customArrows(slider, 'prev', btnPrevIdentifier);
                    customArrows(slider, 'next', btnNextIdentifier);

                    for (let j = 0, len = itemsCount; j < len; j++) {
                        let slide = items[j]
                        slide.classList.remove('overflow-hidden');
                    }

                    // Accessibility
                    slider.querySelectorAll('.btn-pause').forEach(function (psBtn) {
                        if (psBtn.classList.contains('btn-pause')) {
                            psBtn.onclick = function () {
                                const plyBtn = psBtn.closest('.arrows-wrap').querySelector('.btn-play');
                                splide.Components.Autoplay.pause();
                                psBtn.classList.add('d-none');
                                plyBtn.classList.remove('d-none');
                                if (plyBtn) {
                                    plyBtn.onclick = function () {
                                        splide.Components.Autoplay.play();
                                        psBtn.classList.remove('d-none');
                                        plyBtn.classList.add('d-none');
                                    }
                                }
                            }
                        }
                    });

                    return splide;
                }
            }
        }

        sliders.forEach(function (slider) {
            if (!slider.classList.contains('is-initialized') && isInViewport(slider, 300)) {
                init(slider);
            } else if (!slider.classList.contains('is-initialized') && isInViewport(slider, 0)) {
                init(slider);
            }
            window.addEventListener('scroll', function () {
                if (!slider.classList.contains('is-initialized') && isInViewport(slider, 300)) {
                    init(slider);
                }
            });
        });

        function playVideo(slider) {
            let sliderId = slider.getAttribute('id');
            let sliderEl = document.querySelector('#' + sliderId);
            if (sliderEl) {
                let parentSlider = sliderEl.parentNode;
                let inViewport = isInViewport(parentSlider, 200);
                if (inViewport) {
                    setTimeout(function () {
                        let el = parentSlider.querySelector('.splide__slide.is-visible.is-active');
                        let playerYoutube = el ? el.querySelector('.embed-youtube') : false;
                        if (playerYoutube) {
                            import('../../../vendor/components/lazy-videos').then(({playYoutube: PlayYoutube}) => {
                                new PlayYoutube(playerYoutube, 'autoplay');
                            });
                        }
                        let playerHtml = el ? el.querySelector('.html-video') : false;
                        if (playerHtml) {
                            import('../../../vendor/components/lazy-videos').then(({playHtml: PlayHtml}) => {
                                new PlayHtml(playerHtml, 'autoplay');
                            });
                        }
                    }, 50);
                }
            }
        }

    }).catch(error => console.error(error.message));
}