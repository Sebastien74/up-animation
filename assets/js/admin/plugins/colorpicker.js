import 'jquery-asColorPicker/dist/css/asColorPicker.css';
import 'jquery-asColorPicker';

/**
 * Colorpicker
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    let colorPickers = document.getElementsByClassName('colorpicker');
    if (colorPickers.length > 0) {
        $(".colorpicker").asColorPicker();
    }
}