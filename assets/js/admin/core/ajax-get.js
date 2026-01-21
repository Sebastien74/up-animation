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

    let body = $('body');

    body.on('click', '.ajax-get-refresh', function (e) {

        e.preventDefault();

        let el = $(this);
        let target = el.data('target');
        let targetAttr = typeof target != 'undefined' ? target : '.ajax-content';
        let mainLoader = body.find('.main-preloader');
        let loader = body.find(el.data('target-loader'));
        let customPreloader = true;
        let pushHistory = el.data('history');

        if (loader.length < 1) {
            loader = mainLoader;
            customPreloader = false;
        }

        $.ajax({
            url: el.attr('href') + "?ajax=true",
            type: "GET",
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
                $('.alert').remove();
                if (loader.length >= 1) {
                    if (customPreloader) {
                        loader.parent().removeClass('d-none');
                    }
                    loader.removeClass('d-none');
                }
            },
            success: function (response) {

                if (response.html) {

                    let html = $(response.html).find(targetAttr)[0];
                    let body = $('body');
                    let ajaxContent = body.find(targetAttr);
                    ajaxContent.replaceWith(html);

                    if (loader.length >= 1) {
                        loader.addClass('d-none');
                        if (customPreloader) {
                            loader.parent().addClass('d-none');
                        }
                    }

                    $('[data-bs-toggle=tooltip]').tooltip({trigger: "hover"});

                    mainLoader.addClass('d-none');

                    let scrollToEl = body.find('.scroll-to-response-ajax');
                    if (scrollToEl.length >= 1) {
                        $('html, body').animate({scrollTop: scrollToEl.offset().top}, 800);
                    }

                    const inModal = el.closest('.modal');
                    if (!inModal && response.history && typeof pushHistory != 'undefined') {
                        history.pushState({}, null, response.history);
                    }

                    activeSearch();

                    import('../../vendor/components/medias-loader').then(({default: mediaLoader}) => {
                        new mediaLoader();
                    }).catch(error => console.error(error.message));
                }
            },
            error: function (errors) {
                /** Display errors */
                import('../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            }
        });

        e.stopImmediatePropagation();
        return false;
    });
}