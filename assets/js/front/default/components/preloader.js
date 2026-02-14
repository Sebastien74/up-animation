/**
 * Preloader
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    const body = document.body;
    const preloader = document.getElementById("main-preloader");

    if (!preloader) {
        return;
    }

    body.classList.add('overflow-hidden');

    const hidePreloader = () => {
        // Hide preloader and restore scroll
        if (!preloader.classList.contains('disappear')) {
            preloader.classList.add('disappear');
        }
        if (!preloader.classList.contains('d-none')) {
            preloader.classList.add('d-none');
        }
        body.classList.remove('overflow-hidden');
        body.classList.remove('preloader-active');
    };

    // If the page is already loaded (module loaded late), hide immediately
    if (document.readyState === 'complete') {
        hidePreloader();
    } else {
        window.addEventListener("load", hidePreloader, { once: true });
        window.addEventListener('pageshow', (event) => {
            // For bfcache restores or normal navigation
            if (event.persisted || document.readyState === 'complete') {
                hidePreloader();
            }
        }, { once: true });
    }

    // Event delegation for toggling the preloader
    document.addEventListener('click', (e) => {
        const toggleEl = e.target.closest('[data-toggle="preloader"]');
        if (toggleEl && e.which !== 2) {
            body.classList.add('overflow-hidden');
            preloader.classList.remove('disappear');
            preloader.classList.remove('d-none');
        }
        const pageLink = e.target.closest('.pagination a.page-link');
        if (pageLink && pageLink.hasAttribute('role')) {
            preloader.classList.remove('disappear');
            preloader.classList.remove('d-none');
            body.classList.add('preloader-active');
        }
    }, { passive: true });
}