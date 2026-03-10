export default function () {
    let colorPickers = document.querySelectorAll('.colorpicker');
    if (colorPickers.length > 0) {
        import('jquery-asColorPicker/dist/css/asColorPicker.css');
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.asColorPicker !== 'undefined') {
            jQuery(colorPickers).asColorPicker();
        }
    }
}