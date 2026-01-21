import route from "../../vendor/components/routing";
import management from "./management";
import services from "./services";
import Cookies from 'js-cookie'
import Tooltip from '../../front/bootstrap/dist/tooltip'
import Modal from '../../front/bootstrap/dist/modal'

/**
 * Modal
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (forceOpening = false) {

    let body = document.body
    let cookieName = 'felixCookies'
    let FelixCookies = Cookies.get(cookieName)
    let cookiesInit = typeof FelixCookies != 'undefined'
    let gdprActive = body.dataset.gdpr

    if (forceOpening) {
        openModal()
    }

    if (!cookiesInit && parseInt(gdprActive) === 1) {
        window.addEventListener('scroll', function (e) {
            if (!body.classList.contains('scroll-cookies-modal')) {
                body.classList.add('scroll-cookies-modal')
                openModal()
                e.stopImmediatePropagation()
                return false
            }
        })
    }

    let openModalEls = document.getElementsByClassName('open-gdpr-modal')
    for (let i = 0; i < openModalEls.length; i++) {
        let link = openModalEls[i]
        link.onclick = function () {
            link.style.pointerEvents = 'none'
            cookiesInit = true
            openModal(link)
        }
    }

    function openModal(link = null) {

        let xHttp = new XMLHttpRequest()
        xHttp.open("GET", route('front_gdpr_modal', {_format: 'json'}), true)
        xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8")
        xHttp.send()
        xHttp.onload = function (e) {

            if (this.readyState === 4 && this.status === 200) {

                let response = JSON.parse(this.response)
                let htmlElement = document.querySelector("html")
                let body = document.body
                let backdrop = cookiesInit ? true : 'static'

                let html = document.createElement('div')
                html.innerHTML = response.html
                document.body.appendChild(html)

                let modalEl = document.getElementById('gdpr-modal')
                let closeModalEl = modalEl.getElementsByClassName('btn-close')
                let modal = new Modal(modalEl, {
                    backdrop: backdrop,
                    keyboard: cookiesInit
                })
                modal.show()
                htmlElement.classList.add('overflow-hidden')
                body.classList.add('gdpr-modal-open')
                document.getElementsByClassName('modal-backdrop')[0].classList.add('gdpr-modal-backdrop')
                if (closeModalEl.length > 0) {
                    closeModalEl[0].addEventListener('click', function () {
                        modal.hide()
                    })
                }
                modalEl.addEventListener('hidden.bs.modal', function () {
                    modalEl.remove()
                    htmlElement.classList.remove('overflow-hidden')
                    body.classList.remove('gdpr-modal-open')
                })

                switchBlocks()

                let tooltipTriggerList = [].slice.call(modalEl.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new Tooltip(tooltipTriggerEl)
                })

                let preloader = document.getElementById('main-preloader')
                if (preloader) {
                    let preloaderClicks = modalEl.querySelectorAll('[data-toggle="preloader"]')
                    for (let i = 0; i < preloaderClicks.length; i++) {
                        preloaderClicks[i].onclick = function () {
                            preloader.classList.remove('d-none')
                            body.classList.add('preloader-active')
                        }
                    }
                }

                let pathname = window.location.pathname;
                if (pathname.indexOf('cookies') !== -1) {
                    let cookiesWraps = document.getElementsByClassName('cookies-more-wrap')
                    for (let i = 0; i < cookiesWraps.length; i++) {
                        cookiesWraps[i].remove()
                    }
                }

                let moreWrap = document.getElementById('cookies-more-wrap')
                if (moreWrap && preloader) {
                    moreWrap.onclick = function () {
                        if (preloader) {
                            preloader.classList.remove('d-none')
                            body.classList.add('preloader-active')
                            window.location.href = this.querySelectorAll('a').getAttribute('href')
                        }
                    }
                }

                management(modal)
                services()
                loadImages(modalEl)

                if (link) {
                    link.style.pointerEvents = 'auto'
                }
            }
        }
    }

    function switchBlocks() {
        let modal = document.getElementById('gdpr-modal')
        let switchers = modal.querySelectorAll('button.switch')
        for (let i = 0; i < switchers.length; i++) {
            switchers[i].onclick = function (e) {
                let switchBlocks = modal.getElementsByClassName('switch-block')
                for (let j = 0; j < switchBlocks.length; j++) {
                    switchBlocks[j].classList.add('d-none')
                }
                document.getElementById(switchers[i].dataset.target).classList.remove('d-none')
            }
        }
    }

    function loadImages(modal) {
        let images = modal.querySelectorAll('img.lazy-load')
        for (let i = 0; i < images.length; i++) {
            let image = images[i]
            image.src = image.dataset.src
        }
    }
}