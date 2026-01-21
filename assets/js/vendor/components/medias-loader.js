/**
 * Media loader
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let loaderRequest = function () {
        let body = document.body;
        let skinAdmin = body.classList.contains('skin-admin');
        let el = document.querySelector('.hx-include-in-viewport');
        let loader = skinAdmin ? document.getElementById('main-preloader') : null;
        if (loader) {
            loader.classList.remove('d-none');
            loader.style.opacity = '1';
        }
        if (el && !body.classList.contains('media-loader-active')) {
            body.classList.add('media-loader-active');
            let xHttp = new XMLHttpRequest();
            xHttp.open("GET", el.getAttribute('src'), true);
            xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
            xHttp.send();
            xHttp.onload = function () {
                if (this.readyState === 4 && this.status === 200) {
                    if (!el.classList.contains('only-hx')) {
                        let response = JSON.parse(this.response);
                        let loaderWrap = el.closest('.img-loader-wrap');
                        let loader = loaderWrap.querySelector('.img-loader');
                        loaderWrap.innerHTML = response.html;
                        if (loader) {
                            loader.remove();
                        }
                    } else {
                        el.remove();
                    }
                    body.classList.remove('media-loader-active');
                    loaderRequest();
                }
            };
        } else if(!el && loader) {
            loader.classList.add('d-none');
            loader.style.opacity = '0';
        }
    }

    let elInViewport = function (el, offset = 0) {
        const bounding = el.getBoundingClientRect(),
            myElementHeight = el.offsetHeight,
            myElementWidth = el.offsetWidth;
        return bounding.top >= -myElementHeight
            && bounding.left >= -myElementWidth
            && bounding.right <= (window.innerWidth + offset || document.documentElement.clientWidth + offset) + myElementWidth
            && bounding.bottom <= (window.innerHeight || document.documentElement.clientHeight) + myElementHeight;
    }

    let inViewport = function (offset = 0) {
        let els = document.querySelectorAll('hx\\:include');
        els.forEach(function (el) {
            if (elInViewport(el, offset)) {
                el.classList.add('hx-include-in-viewport')
            }
        });
        loaderRequest();
    }

    inViewport();
    window.onscroll = function () {
        inViewport(300);
    }
};