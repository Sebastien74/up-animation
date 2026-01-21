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
                body.style.marginBottom = websiteAlert.offsetHeight + 'px';
                body.style.position = 'relative';
                body.style.bottom = '-2px';
            }
        }
    }
    websiteAlertDisplay();
    window.addEventListener('resize', websiteAlertDisplay);

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
                    } else if ('top' === position) {
                        boxAlert.style.marginTop = '-' + height;
                        boxAlert.style.transition = 'margin-top .5s ease-in-out';
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
     * Apply the top offset to fixed navigation based on alert height.
     */
    function applyOffset() {
        const alertHeight = boxAlert ? boxAlert.offsetHeight : 0;
        const navStyle = window.getComputedStyle(navigation);
        const isFixed = navStyle.position === 'fixed';
        if (!isFixed) {
            navigation.style.top = '';
            body.style.paddingTop = '';
            return;
        }
        navigation.style.top = `${alertHeight}px`;
    }
    if (navigation) {
        applyOffset();
        window.addEventListener('resize', applyOffset, { passive: true });
    }
}