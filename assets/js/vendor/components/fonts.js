import loadStylesheets from '../components/load-stylesheets';

/**
 * Fonts
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let fontsElData = $('#data-fonts');

    if (typeof fontsElData != 'undefined') {

        let fonts = fontsElData.find('.font-data');

        fonts.each(function () {
            loadStylesheets("/build/fonts/font-" + $(this).data('font') + ".css", !$('.title-header-block').length > 0);
        });
    }
};