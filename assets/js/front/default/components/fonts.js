/**
 * Fonts.
 *
 *  To set color to children element if parent is font element.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    /** To set color to children element if parent is font element */
    let fontsElements = document.getElementsByTagName('font');
    for (let i = 0; i < fontsElements.length; i++) {
        let fontElement = fontsElements[i];
        let color = fontElement.getAttribute('color');
        if (color) {
            let children = fontElement.querySelectorAll('*');
            for (let j = 0; j < children.length; j++) {
                if ((j + 1) === children.length) {
                    let child = children[j];
                    let elStyle = child.getAttribute('style');
                    let style = elStyle ? elStyle + ' color: ' + color + ' !important' : 'color: ' + color + ' !important;';
                    child.setAttribute('style', style);
                }
            }
            let mainElStyle = fontElement.getAttribute('style');
            let mainStyle = mainElStyle ? mainElStyle + ' color: ' + color + ' !important' : 'color: ' + color + ' !important;';
            fontElement.setAttribute('style', mainStyle);
        }
    }
}