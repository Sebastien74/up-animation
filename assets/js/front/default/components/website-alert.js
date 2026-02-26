/**
 * Website alert.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @Doc: https://www.jsdelivr.com/package/npm/vanilla-infinite-marquee
 */

import '../../../../scss/front/default/components/_website-alert.scss';

export default function () {

    const body = document.body;
    const boxAlert = document.getElementById('website-alert');
    const navigation = document.getElementById('menu-container-main');
    const type = boxAlert ? boxAlert.dataset.type : false;

    boxAlert.classList.remove('d-none');

    const websiteAlertDisplay = function () {
        if (body.classList.contains('alert-active')) {
            const websiteAlert = document.getElementById('website-alert');
            const position = websiteAlert ? websiteAlert.dataset.position : false;
            if ('bottom' === position) {
                const alertHeight = websiteAlert.offsetHeight;
                body.style.marginBottom = alertHeight + 'px';
                body.style.position = 'relative';
                body.style.bottom = '-2px';
            }
        }
    }
    websiteAlertDisplay();
    window.addEventListener('resize', () => {
        if (websiteAlertDisplay._raf) {
            cancelAnimationFrame(websiteAlertDisplay._raf);
        }
        websiteAlertDisplay._raf = requestAnimationFrame(() => {
            websiteAlertDisplay();
            websiteAlertDisplay._raf = null;
        });
    }, {passive: true});

    if (boxAlert) {

        const marqueeEl = boxAlert.querySelector('.marquee-container');
        if (marqueeEl) {
            import('vanilla-infinite-marquee').then(({default: InfiniteMarquee}) => {
                new InfiniteMarquee({
                    element: marqueeEl,
                    speed: boxAlert.dataset.speed,
                    smoothEdges: true,
                    pauseOnHover: true,
                    direction: boxAlert.dataset.direction,
                    gap: boxAlert.dataset.gap,
                    duplicateCount: boxAlert.dataset.duplicate,
                    mobileSettings: {
                        direction: boxAlert.dataset.directionMobile,
                        speed: boxAlert.dataset.speedMobile,
                    },
                    on: {
                        beforeInit: () => {
                            // console.log('Not Yet Initialized');
                        },
                        afterInit: () => {
                            // console.log('Initialized');
                        }
                    }
                });
            }).catch(error => console.error(error.message));
        }

        if (type === 'flip') {
            import('./flip-carousel').then(({default: FlipCarousel}) => {
                new FlipCarousel('.flip-container', {
                    interval: 3000, // temps entre chaque changement (ms)
                    speed: 800      // durée de la transition (ms)
                });
            }).catch(error => console.error(error.message));
        }

        const position = boxAlert.dataset.position;
        const closeAlert = document.getElementById('close-website-alert');

        if (closeAlert) {

            closeAlert.addEventListener('click', () => {
                let isActive = !boxAlert.classList.contains('disabled');
                let currentStatus = isActive ? 'show' : 'hide';
                let oReq = new XMLHttpRequest();
                oReq.onload = reqListener;
                oReq.open("get", closeAlert.dataset.path + '?currentStatus=' + currentStatus, true);
                oReq.send();
            })

            function reqListener() {
                let response = JSON.parse(this.responseText);
                if (response.success) {
                    const height = boxAlert.clientHeight + 'px';
                    const navigationContainer = document.querySelector('#menu-container-main');
                    let stickyNav = navigationContainer ? window.getComputedStyle(navigationContainer).position === 'sticky' : false;
                    if (!stickyNav) {
                        const navigation = document.querySelector('#main-navigation');
                        stickyNav = navigation ? window.getComputedStyle(navigation).position === 'sticky' : false;
                    }
                    body.classList.add('remove-alert-' + position);
                    body.classList.remove('alert-active');
                    if ('top' === position && stickyNav) {
                        body.style.marginTop = '-' + height;
                        if (navigation) {
                            navigation.style.transition = 'top .5s ease-in-out';
                            navigation.style.top = '0px';
                        }
                    } else if ('top' === position) {
                        boxAlert.style.marginTop = '-' + height;
                        boxAlert.style.transition = 'margin-top .5s ease-in-out';
                        if (navigation) {
                            navigation.style.transition = 'top .5s ease-in-out';
                            navigation.style.top = '0px';
                        }
                    } else if ('bottom' === position) {
                        boxAlert.style.bottom = '-' + height;
                        body.style.marginBottom = '0';
                    }
                    setTimeout(function () {
                        boxAlert.remove();
                    }, 1000);
                }
            }
        }
    }

    /**
     * Apply the top offset to fixed navigation based on the visible alert height (on scroll/resize).
     */
    function applyOffset() {

        if (!navigation) {
            return;
        }

        const navStyle = window.getComputedStyle(navigation);
        const isFixed = navStyle.position === 'fixed';

        if (!isFixed) {
            return;
        }

        // If an alert is missing/removed or not a top alert, no offset.
        if (!boxAlert || !boxAlert.isConnected || boxAlert.dataset.position !== 'top') {
            navigation.style.top = '0px';
            return;
        }

        // Visible part of the alert in viewport:
        // - at the top of the page: rect.bottom ~= alertHeight
        // - when scrolling down: rect.bottom decreases to 0
        const rect = boxAlert.getBoundingClientRect();
        const alertHeight = boxAlert.offsetHeight;

        const visible = Math.max(0, Math.min(alertHeight, rect.bottom));
        navigation.style.top = `${visible}px`;
    }

    /**
     * rAF throttle for scroll/resize callbacks.
     */
    function scheduleApplyOffset() {
        if (scheduleApplyOffset._raf) {
            return;
        }
        scheduleApplyOffset._raf = requestAnimationFrame(() => {
            scheduleApplyOffset._raf = null;
            applyOffset();
        });
    }

    scheduleApplyOffset._raf = null;

    if (navigation) {
        applyOffset();
        window.addEventListener('resize', scheduleApplyOffset, {passive: true});
        window.addEventListener('scroll', scheduleApplyOffset, {passive: true});
    }
}