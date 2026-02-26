import activeSearch from "../media/library";

/**
 * Ajax GET refresh
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let preloader = document.querySelector("#main-preloader");
    document.querySelectorAll('.modal-btn-position-ajax').forEach(function (el) {
        el.onclick = function (ev) {
            ev.preventDefault();
            if (preloader) {
                preloader.classList.remove('d-none');
                preloader.style.opacity = '1';
            }
            let xHttp = new XMLHttpRequest();
            let href = el.getAttribute('href');
            let url = href.indexOf('?') > -1 ? href + '&ajax-view=true' : href + '?ajax-view=true';
            xHttp.open("GET", url, true);
            xHttp.send();
            xHttp.onload = function () {
                if (this.readyState === 4 && this.status === 200) {
                    let response = this.response;
                    response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
                    response = JSON.parse(response);
                    let htmlEl = document.createElement('div')
                    htmlEl.innerHTML = response.html + '<div class="modal-backdrop fade show"></div>';
                    document.body.appendChild(htmlEl);
                    if (preloader) {
                        preloader.classList.add('d-none');
                        preloader.style.opacity = '0';
                    }
                    let modal = document.getElementById(el.dataset.modal);
                    if (modal) {
                        modal.querySelectorAll('.btn-dismiss').forEach(function (btn) {
                            btn.onclick = function (ev) {
                                ev.preventDefault();
                                modal.remove();
                                document.querySelectorAll('.modal-backdrop').forEach(function (el) {
                                    el.remove();
                                });
                            }
                        });
                    }
                }
            }
        }
    });

    document.addEventListener('click', function (e) {
        const refreshEl = e.target.closest('.ajax-get-refresh');
        if (refreshEl) {
            e.preventDefault();

            const target = refreshEl.dataset.target;
            const targetAttr = typeof target !== 'undefined' ? target : '.ajax-content';
            const mainLoader = document.body.querySelector('.main-preloader');
            const targetLoaderSelector = refreshEl.dataset.targetLoader;
            let loader = targetLoaderSelector ? document.body.querySelector(targetLoaderSelector) : null;
            let customPreloader = true;
            const pushHistory = refreshEl.dataset.history;

            if (!loader) {
                loader = mainLoader;
                customPreloader = false;
            }

            const url = refreshEl.getAttribute('href') + (refreshEl.getAttribute('href').includes('?') ? "&ajax=true" : "?ajax=true");

            document.querySelectorAll('.alert').forEach(alert => alert.remove());
            if (loader) {
                if (customPreloader && loader.parentElement) {
                    loader.parentElement.classList.remove('d-none');
                }
                loader.classList.remove('d-none');
            }

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json())
                .then(response => {
                    if (response.html) {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = response.html;
                        const newContent = tempDiv.querySelector(targetAttr);

                        if (newContent) {
                            const ajaxContent = document.body.querySelector(targetAttr);
                            if (ajaxContent) {
                                ajaxContent.replaceWith(newContent);
                            }
                        }

                        if (loader) {
                            loader.classList.add('d-none');
                            if (customPreloader && loader.parentElement) {
                                loader.parentElement.classList.add('d-none');
                            }
                        }

                        const tooltips = document.querySelectorAll('[data-bs-toggle=tooltip]');
                        tooltips.forEach(t => {
                            if (typeof bootstrap !== 'undefined') {
                                new bootstrap.Tooltip(t, {trigger: "hover"});
                            }
                        });

                        if (mainLoader) mainLoader.classList.add('d-none');

                        const scrollToEl = document.body.querySelector('.scroll-to-response-ajax');
                        if (scrollToEl) {
                            window.scrollTo({
                                top: scrollToEl.getBoundingClientRect().top + window.pageYOffset,
                                behavior: 'smooth'
                            });
                        }

                        const inModal = refreshEl.closest('.modal');
                        if (!inModal && response.history && typeof pushHistory !== 'undefined') {
                            history.pushState({}, null, response.history);
                        }

                        activeSearch();

                        import('../../vendor/components/medias-loader').then(({default: mediaLoader}) => {
                            new mediaLoader();
                        }).catch(error => console.error(error.message));
                    }
                })
                .catch(errors => {
                    /** Display errors */
                    import('../core/errors').then(({default: displayErrors}) => {
                        new displayErrors(errors);
                    }).catch(error => console.error(error.message));
                });

            e.stopImmediatePropagation();
        }
    });
}