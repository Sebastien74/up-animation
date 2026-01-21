/**
 * Preloader
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

const body = document.body;
const preloader = document.getElementById("main-preloader");

if (preloader) {

    preloader.classList.add('d-none');

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            if (!preloader.classList.contains('d-none')) {
                preloader.classList.add('d-none');
            }
        }
    });

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener("submit", function (e) {
            preloader.classList.remove('d-none');
        });
    });
}