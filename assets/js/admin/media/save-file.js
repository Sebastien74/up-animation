import resetModal from "../../vendor/components/reset-modal";
import route from "../core/routing";

/**
 * Save files
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (Routing, e, el) {

    let body = $('body');
    let mediasModal = body.find('#medias-library-modal');
    let options = el.data('options');
    let files = mediasModal.find('.file.active');
    let type = mediasModal.data('type');

    let addMedia = function ({file, body, options, type, media, src, mediasModal}) {

        let loader = file.find('.loader-media');

        $.ajax({
            url: route(Routing, 'admin_medias_modal_add', {
                "website": body.data('id'),
                "media": media,
                "options": JSON.stringify(options)
            }),
            type: "GET",
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
                loader.removeClass('d-none');
            },
            success: function () {
                file.removeClass('active');
                let loaders = $('body').find('#medias-library-modal').find('.file.active').length;
                if (loaders === 0 && type === 'multiple') {
                    resetModal(mediasModal, true);
                    $('#main-preloader').removeClass('d-none');
                    location.reload();
                } else if (type === 'single') {
                    let dropifyWrapper = $(options.btnId).parent().parent().find('.dropify-wrapper');
                    let render = dropifyWrapper.find('.dropify-render').find('img');
                    if (render.length > 0) {
                        render.attr('src', src);
                    } else {
                        let regex = /\.(mp4|vtt|webm)$/i;
                        let renderView = dropifyWrapper.find('.dropify-message');
                        let match = src ? src.match(regex) : false;
                        if (match) {
                            renderView.html('<span class="dropify-render"><i class="dropify-font-file"></i><span class="dropify-extension">' + match[0] + '</span></span>');
                        } else {
                            renderView.html('<img src="' + src + '" alt="placeholder" />');
                        }
                    }
                    resetModal(mediasModal, true);
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
    };

    files.each(function () {
        let file = $(this);
        let src = $(this).attr('data-original-src');
        addMedia({
            file: file,
            body: body,
            options: options,
            type: type,
            media: $(this).data('id'),
            src: src,
            mediasModal: mediasModal
        });
    });
}