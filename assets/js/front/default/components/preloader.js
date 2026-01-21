/**
 * Preloader
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let body = document.body;
    let preloader = document.getElementById("main-preloader");

    if (preloader) {

        body.classList.add('overflow-hidden');

        window.addEventListener("load", function () {
            if (!preloader.classList.contains('disappear')) {
                preloader.classList.add('disappear');
                preloader.classList.add('d-none');
                body.classList.remove('overflow-hidden')
            } else if (!preloader.classList.contains('d-none')) {
                body.classList.add('d-none');
            }
        })

        window.addEventListener('pageshow', function (event) {
            if (!preloader.classList.contains('disappear')) {
                preloader.classList.add('disappear');
                preloader.classList.add('d-none');
                body.classList.remove('overflow-hidden')
            } else if (event.persisted) {
                if (!preloader.classList.contains('d-none')) {
                    body.classList.add('d-none');
                }
            }
        })

        document.querySelectorAll('[data-toggle="preloader"]').forEach(function (preloader) {
            preloader.addEventListener("click", function (e) {
                if (e.which !== 2) {
                    body.classList.add('overflow-hidden');
                    preloader.classList.remove('disappear');
                    preloader.classList.remove('d-none');
                }
            });
        });

        document.querySelectorAll('.pagination a.page-link').forEach(function (link) {
            link.addEventListener("click", function () {
                if (this.hasAttribute("role")) {
                    preloader.classList.remove('disappear')
                    preloader.classList.remove('d-none')
                    body.classList.add('preloader-active')
                }
            });
        });
    }
}