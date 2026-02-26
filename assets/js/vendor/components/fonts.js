import loadStylesheets from '../components/load-stylesheets';

/**
 * Fonts
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    let fontsElData = document.getElementById('data-fonts');
    if (fontsElData) {
        let fonts = fontsElData.querySelectorAll('.font-data');
        fonts.forEach(function (font) {
            let fontName = font.dataset.font;
            let hasHeaderBlock = document.querySelector('.title-header-block') !== null;
            loadStylesheets("/build/fonts/font-" + fontName + ".css", !hasHeaderBlock);
        });
    }
};