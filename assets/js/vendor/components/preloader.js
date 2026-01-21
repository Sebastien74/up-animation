/**
 * Preloader
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

let body = document.body
let preloader = document.getElementById("main-preloader")

let preloaderEls = document.querySelectorAll('[data-bs-toggle="preloader"]')
for (let i = 0; i < preloaderEls.length; i++) {
    preloaderEls[i].addEventListener("click", function (e) {
        if (e.which !== 2) {
            body.classList.remove('d-none')
        }
    })
}

window.addEventListener('pageshow', function (event) {
    if (!preloader.classList.contains('d-none')) {
        preloader.classList.add('d-none')
    }
    body.classList.remove('preloader-active')
})

let paginationItems = document.querySelectorAll('.pagination .page-item')
for (let i = 0; i < paginationItems.length; i++) {
    paginationItems[i].addEventListener("click", function () {
        if (!this.classList.contains("active")) {
            preloader.classList.toggle('d-none')
        }
    })
}