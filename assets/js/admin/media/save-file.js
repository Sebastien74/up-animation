import resetModal from "../../vendor/components/reset-modal";
import route from "../core/routing";

/**
 * Save files
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (Routing, e, el) {

    let body = document.body;
    let mediasModal = body.querySelector('#medias-library-modal');
    let options = el instanceof HTMLElement ? JSON.parse(el.dataset.options || '{}') : el.data('options');
    let files = mediasModal ? mediasModal.querySelectorAll('.file.active') : [];
    let type = mediasModal ? mediasModal.dataset.type : null;
    if (!type) {
        let saveBtn = body.querySelector('#save-file-library');
        type = saveBtn ? saveBtn.dataset.type : null;
    }

    let addMedia = function ({file, body, options, type, media, src, mediasModal}) {

        let loader = file.querySelector('.loader-media');

        let url = route(Routing, 'admin_medias_modal_add', {
            "website": body.dataset.id,
            "media": media,
            "options": JSON.stringify(options)
        });

        if (loader) {
            loader.classList.remove('d-none');
        }

        fetch(url, {
            method: "GET",
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw response;
                }
                return response.json();
            })
            .then(response => {
                file.classList.remove('active');
                let activeFiles = body.querySelector('#medias-library-modal').querySelectorAll('.file.active').length;
                if (activeFiles === 0 && type === 'multiple') {
                    resetModal(mediasModal, true);
                    let mainLoader = document.getElementById('main-preloader');
                    if (mainLoader) {
                        mainLoader.classList.remove('d-none');
                    }
                    location.reload();
                } else if (type === 'single') {
                    let btnId = options.btnId;
                    let btn = document.getElementById(btnId.replace('#', ''));
                    let dropifyWrapper = btn ? btn.closest('.dropify-wrapper') : null;
                    if (dropifyWrapper) {
                        let render = dropifyWrapper.querySelector('.dropify-render img');
                        if (render) {
                            render.setAttribute('src', src);
                        } else {
                            let regex = /\.(mp4|vtt|webm)$/i;
                            let renderView = dropifyWrapper.querySelector('.dropify-message');
                            let match = src ? src.match(regex) : false;
                            if (match) {
                                renderView.innerHTML = '<span class="dropify-render"><i class="dropify-font-file"></i><span class="dropify-extension">' + match[0] + '</span></span>';
                            } else {
                                renderView.innerHTML = '<img src="' + src + '" alt="placeholder" />';
                            }
                        }
                    }
                    resetModal(mediasModal, true);
                }
            })
            .catch(errors => {
                /** Display errors */
                import('../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            });

        e.stopImmediatePropagation();
        return false;
    };

    files.forEach(function (file) {
        let src = file.getAttribute('data-original-src');
        addMedia({
            file: file,
            body: body,
            options: options,
            type: type,
            media: file.dataset.id,
            src: src,
            mediasModal: mediasModal
        });
    });
}