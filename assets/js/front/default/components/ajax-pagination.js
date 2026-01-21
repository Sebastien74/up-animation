import {scrollToEL, isInViewport} from "../functions";

/**
 * Ajax pagination
 */
export default function () {

    const hideLoader = function () {
        const loader = document.querySelector('.results-loader');
        if (loader) {
            loader.classList.add('d-none');
        }
    }

    const exec = function (pagination) {

        hideLoader();

        if (pagination) {

            const fullDomain = window.location.protocol + '//' + window.location.host;
            const wrapper = pagination.closest('#scroll-wrapper');
            const baseUrl = wrapper ? fullDomain + wrapper.dataset.href : false;

            function updatePageParam(pageID) {
                const url = new URL(window.location.href);
                url.searchParams.set('page', pageID);
                window.history.replaceState({}, document.title, url.toString());
            }

            pagination.querySelectorAll('.page-link:not(.active):not(.disabled)').forEach(link => {

                link.onclick = function (e) {
                    e.preventDefault();
                    const loader = document.querySelector('.results-loader');
                    if (loader) {
                        loader.classList.remove('d-none');
                    }
                    const pageID = link.dataset.page;
                    const xHttp = new XMLHttpRequest();
                    const url = new URL(baseUrl);
                    const linkUrl = new URL(link.href);
                    linkUrl.searchParams.forEach((value, key) => {
                        url.searchParams.set(key, value);
                    });
                    url.searchParams.set('ajax', 'true');
                    url.searchParams.set('page', pageID);
                    xHttp.open("GET", url.href, true);
                    xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
                    xHttp.send();
                    xHttp.onload = function () {
                        if (this.readyState === 4 && this.status === 200) {
                            const response = JSON.parse(this.response);
                            let html = document.createElement('div');
                            html.innerHTML = response.html;
                            let container = link.closest('.pagination-ajax-wrap');
                            const render = html.querySelector('.pagination-ajax-wrap');
                            container.innerHTML = render.innerHTML;
                            import('../../../vendor/components/medias-loader').then(({default: mediaLoader}) => {
                                new mediaLoader();
                            }).catch(error => console.error(error.message));
                            const firstItem = container.querySelector('.item');
                            if (!isInViewport(firstItem)) {
                                scrollToEL(firstItem, false);
                            }
                            updatePageParam(pageID);
                            hideLoader();
                            document.querySelectorAll('.ajax-pagination').forEach(el => {
                                exec(el);
                            });
                        }
                    }
                }
            });
        }
    }

    const paginations = document.querySelectorAll('.ajax-pagination');
    paginations.forEach(el => {
        exec(el);
    });

    if (paginations.length === 0) {
        hideLoader();
    }
}