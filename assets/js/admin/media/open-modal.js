import route from "../core/routing";
import activeSearch from "./library";

/**
 * Media library modal
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
export default function (Routing, e, el) {

    let body = document.body;
    let referPreloader = el.closest('.refer-preloader');
    let stripePreloader = referPreloader ? referPreloader.querySelector('.stripe-preloader') : null;
    let loader = stripePreloader ? stripePreloader : body.querySelector('.main-preloader');
    if (loader) {
        loader.classList.remove('d-none');
        loader.setAttribute('style', 'opacity: 1;');
    }

    /** Open modal */

    let options = el.dataset.options;
    let url = route(Routing, 'admin_medias_modal', {
        "website": body.dataset.id,
        "options": JSON.stringify(options)
    });

    fetch(url, {
        method: "GET",
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(response => {

            body.insertAdjacentHTML('beforeend', response.html);

            if (loader) {
                loader.classList.add('d-none');
                // loader.setAttribute('style', 'opacity: 0;');
            }

            let modalEl = body.querySelector('#medias-library-modal');
            import('../bootstrap/dist/modal').then(({default: Modal}) => {
                let modal = new Modal(modalEl);
                modal.show();
            }).catch(error => console.error(error.message));

            modalEl.querySelectorAll('.btn-edit, .btn-zip').forEach(btn => btn.remove());

            import('../plugins/nestable').then(({default: nestable}) => {
                nestable();
            }).catch(error => console.error(error.message));

            import('../plugins/tooltips').then(({default: tooltips}) => {
                tooltips();
            }).catch(error => console.error(error.message));

            activeSearch();

            import('../../vendor/components/medias-loader').then(({default: mediaLoader}) => {
                new mediaLoader();
            }).catch(error => console.error(error.message));

            modalEl.addEventListener('hidden.bs.modal', function (e) {
                modalEl.remove();
            });

            modalEl.addEventListener('click', function (e) {

                let saveBtn = e.target.closest('#save-file-library');
                if (saveBtn) {
                    e.preventDefault();
                    let modalLoader = body.querySelector('#modal-preloader');
                    if (modalLoader) {
                        modalLoader.classList.remove('d-none');
                    }
                    import('./save-file').then(({default: saveFile}) => {
                        new saveFile(Routing, e, saveBtn);
                    }).catch(error => console.error(error.message));
                }

                let dataWrapBtn = e.target.closest('.file-data-wrap');
                if (dataWrapBtn) {
                    e.preventDefault();
                    import('./data-wrap').then(({default: dataWrap}) => {
                        new dataWrap(e, dataWrapBtn);
                    }).catch(error => console.error(error.message));
                }

                let refreshBtn = e.target.closest('#medias-library-modal .ajax-get-refresh');
                if (refreshBtn) {
                    e.preventDefault();
                    modalEl.querySelectorAll('.ajax-get-refresh').forEach(btn => {
                        btn.classList.remove('btn-outline-info');
                        btn.classList.add('btn-info');
                    });
                    refreshBtn.classList.remove('btn-info');
                    refreshBtn.classList.add('btn-outline-info');
                }
            });
        })
        .catch(errors => {
            import('../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
        });

    e.stopImmediatePropagation();
    return false;
}