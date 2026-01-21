import route from "../core/routing";
import activeSearch from "./library";

/**
 * Media library modal
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
export default function (Routing, e, el) {

    let body = $('body');
    let stripePreloader = $(el).closest('.refer-preloader').find('.stripe-preloader');
    let loader = stripePreloader.length > 0 ? stripePreloader : body.find('.main-preloader');
    loader.removeClass('d-none');
    loader.attr('style', 'opacity: 1;');

    /** Open modal */

    let url = route(Routing, 'admin_medias_modal', {
        "website": body.data('id'),
        "options": JSON.stringify($(el).data('options'))
    });
    let xHttp = new XMLHttpRequest();
    xHttp.open("GET", url, true);
    xHttp.send();
    xHttp.onload = function () {
        if (this.readyState === 4 && this.status === 200) {

            let response = this.response;
            response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
            response = JSON.parse(response);

            let body = $('body');
            body.append(response.html);

            loader.addClass('d-none');
            loader.attr('style', 'opacity: 0;');

            let modal = body.find('#medias-library-modal');
            modal.modal('show');
            modal.find('.btn-edit').remove();
            modal.find('.btn-zip').remove();

            import('../plugins/nestable').then(({default: nestable}) => {
                new nestable();
            }).catch(error => console.error(error.message));

            import('../plugins/tooltips').then(({default: tooltips}) => {
                new tooltips();
            }).catch(error => console.error(error.message));

            activeSearch();

            import('../../vendor/components/medias-loader').then(({default: mediaLoader}) => {
                new mediaLoader();
            }).catch(error => console.error(error.message));

            modal.on('hidden.bs.modal', function (e) {
                modal.remove();
            });

            modal.on('click', '#save-file-library', function (e) {
                e.preventDefault();
                let loader = $('body').find('#modal-preloader');
                loader.removeClass('d-none');
                import('./save-file').then(({default: saveFile}) => {
                    new saveFile(Routing, e, $(this));
                }).catch(error => console.error(error.message));
            });

            modal.on('click', '.file-data-wrap', function (e) {
                e.preventDefault();
                import('./data-wrap').then(({default: dataWrap}) => {
                    new dataWrap(e, $(this));
                }).catch(error => console.error(error.message));
            });

            modal.on('click', '#medias-library-modal .ajax-get-refresh', function (e) {
                e.preventDefault();
                $('body').find('#medias-library-modal .ajax-get-refresh').removeClass('btn-outline-info').addClass('btn-info');
                $(this).removeClass('btn-info').addClass('btn-outline-info');
            });
        }
    }
    xHttp.onerror = function (errors) {
        import('../core/errors').then(({default: displayErrors}) => {
            new displayErrors(errors);
        }).catch(error => console.error(error.message));
    };

    e.stopImmediatePropagation();
    return false;
}