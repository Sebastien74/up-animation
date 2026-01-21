import {lazyVideos} from "../../../vendor/components/lazy-videos";

/**
 * Show more index
 */
export default function (button) {

    const currentUrl = window.location.href;

    let showMore = function (button) {
        let scroller = document.getElementById('scroll-wrapper');
        let baseUrl = scroller.dataset.href;
        let scrollerId = scroller.getAttribute('id');
        let loader = document.getElementById('scroller-loader');
        button.onclick = function (event) {
            event.preventDefault();
            let scroller = document.getElementById(scrollerId);
            let maxPage = parseInt(scroller.getAttribute('data-max'));
            getItems(scroller, baseUrl, maxPage, loader, button);
            document.activeElement.blur();
        }
    }

    showMore(button)

    let getItems = function (scroller, baseUrl, maxPage, loader, button) {

        let pageID = parseInt(scroller.getAttribute('data-page')) + 1;
        let url = baseUrl + "?scroll-ajax=true&page=" + pageID;
        if (baseUrl.indexOf('?') > -1) {
            url = baseUrl + "&scroll-ajax=true&page=" + pageID;
        }

        let loaded = scroller.getAttribute('data-load');

        if (pageID <= maxPage && parseInt(maxPage) !== 1 && !loaded) {

            scroller.setAttribute('data-load', true);

            let beforeSend = function () {
                loader.classList.remove('d-none');
            }

            let xHttp = new XMLHttpRequest();
            xHttp.open("GET", url, true);
            xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
            beforeSend();
            xHttp.send();
            xHttp.onload = function () {
                if (this.readyState === 4 && this.status === 200) {
                    let response = JSON.parse(this.response);
                    let html = document.createElement('div');
                    html.innerHTML = response.html;
                    let container = document.getElementById('results');
                    html.querySelector('.results').querySelectorAll('.item:not(.link-box)').forEach(item => {
                        container.insertAdjacentHTML('beforeend', item.outerHTML);
                    });
                    import('../../../vendor/components/medias-loader').then(({default: mediaLoader}) => {
                        new mediaLoader();
                    }).catch(error => console.error(error.message));
                    let audios = container.getElementsByClassName('audio-player');
                    if (audios.length > 0) {
                        import('../components/audio').then(({default: audio}) => {
                            new audio(audios)
                        }).catch(error => console.error(error.message));
                    }
                    let popups = container.getElementsByClassName('audio-player');
                    if (popups.length > 0) {
                        import('../../../vendor/plugins/popup').then(({default: popup}) => {
                            new popup()
                        }).catch(error => console.error(error.message));
                    }
                    let videosEl = container.getElementsByClassName("lazy-video");
                    let videosYoutube = container.getElementsByClassName("embed-youtube");
                    if (videosYoutube.length > 0 || videosEl.length > 0) {
                        import("../../../vendor/components/lazy-videos").then(({lazyVideos: LazyVideos}) => {
                            new LazyVideos(videosYoutube, videosEl, true)
                        }).catch(error => console.error(error.message));
                    }
                    loader.classList.add('d-none');
                    scroller.setAttribute('data-page', pageID);
                    scroller.removeAttribute('data-load');
                    if (pageID === maxPage) {
                        button.classList.add('d-none');
                    }
                }
            }
        }
    }
}