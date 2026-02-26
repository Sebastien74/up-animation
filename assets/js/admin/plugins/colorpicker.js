import 'jquery-asColorPicker/dist/css/asColorPicker.css';
import 'jquery-asColorPicker';

/**
 * Colorpicker
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    let colorPickers = document.querySelectorAll('.colorpicker');
    if (colorPickers.length > 0 && typeof jQuery !== 'undefined' && typeof jQuery.fn.asColorPicker !== 'undefined') {
        jQuery(colorPickers).asColorPicker();
    }
}