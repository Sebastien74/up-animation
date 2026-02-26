import dropifyJS from "../form/dropify";
import {tinymcePlugin} from "../plugins/tinymce";
import select2 from "../../vendor/plugins/select2";
import '../bootstrap/dist/tooltip';

export default function (Routing, el) {

    document.querySelectorAll('.media-tab-content-loader.active').forEach(tab => {
        tab.classList.remove('active');
        const item = tab.closest('.sortable-item');
        if (item) {
            item.querySelectorAll('.collapse').forEach(collapse => {
                collapse.classList.remove('show');
            });
        }
        tab.classList.remove('show', 'collapse');
    });

    const rect = el.getBoundingClientRect();
    window.scrollTo({
        top: rect.top + window.pageYOffset - 50,
        behavior: 'smooth'
    });

    if (!el.classList.contains('active')) {

        let targetSelector = el.getAttribute('href');
        let targetEl = document.querySelector(targetSelector);
        let contentWrap = targetEl ? targetEl.querySelector('.card-body') : null;

        let path = el.dataset.path;
        let url = path + (path.includes('?') ? '&ajax=true' : '?ajax=true');

        el.classList.add('active');

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(response => {
                if (response.html && contentWrap) {
                    contentWrap.innerHTML = response.html;
                    dropifyJS();
                    tinymcePlugin();
                    select2();
                    import('./../form/btn-group-toggle').then(({default: btnToggle}) => {
                        new btnToggle();
                    }).catch(error => console.error(error.message));

                    import('../form/ajax').then(({default: ajaxPost}) => {
                        new ajaxPost();
                    }).catch(error => console.error(error.message));

                    if (typeof bootstrap !== 'undefined') {
                        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(t => {
                            new bootstrap.Tooltip(t);
                        });
                    }

                    const newRect = el.getBoundingClientRect();
                    window.scrollTo({
                        top: newRect.top + window.pageYOffset - 50,
                        behavior: 'smooth'
                    });

                    let mediasModals = document.querySelectorAll('.open-modal-medias')
                    for (let i = 0; i < mediasModals.length; i++) {
                        let modalEl = mediasModals[i]
                        modalEl.onclick = function (e) {
                            e.preventDefault()
                            import('../media/open-modal').then(({default: openModal}) => {
                                new openModal(Routing, e, modalEl)
                            }).catch(error => console.error(error.message));
                        }
                    }
                }
            })
            .catch(errors => {
                /** Display errors */
                import('./errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            });
    }
}