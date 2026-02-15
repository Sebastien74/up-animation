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

    let body = $('body');

    $('html, body').animate({scrollTop: $(el).offset().top - 50}, 100);

    if (!$(el).hasClass('active')) {

        let target = $(el).attr('href');
        let contentWrap = body.find(target).find('.card-body');

        let path = $(el).data('path');
        let url = path + '?ajax=true';
        if (path.indexOf('?') > -1) {
            url = path + '&ajax=true'
        }

        $.ajax({
            url: url,
            type: "GET",
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
                $(el).addClass('active');
            },
            success: function (response) {
                if (response.html) {
                    contentWrap.html(response.html);
                    dropifyJS();
                    tinymcePlugin();
                    select2();
                    import('./../form/btn-group-toggle').then(({default: btnToggle}) => {
                        new btnToggle();
                    }).catch(error => console.error(error.message));
                    import('../../vendor/plugins/touchspin').then(({default: touchSpin}) => {
                        new touchSpin();
                    }).catch(error => console.error(error.message));
                    import('../form/ajax').then(({default: ajaxPost}) => {
                        new ajaxPost();
                    }).catch(error => console.error(error.message));
                    $('[data-bs-toggle="tooltip"]').tooltip();
                    $('html, body').animate({scrollTop: $(el).offset().top - 50}, 100);
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
            },
            error: function (errors) {
                /** Display errors */
                import('./errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            }
        });
    }
}