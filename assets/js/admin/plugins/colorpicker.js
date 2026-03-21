export default function () {
    let colorPickers = document.querySelectorAll('.colorpicker');
    if (colorPickers.length > 0) {
        import('jquery-asColorPicker/dist/css/asColorPicker.css');
        import('jquery-asColorPicker').then(() => {
            if (typeof jQuery !== 'undefined' && typeof jQuery.fn.asColorPicker !== 'undefined') {
                jQuery(colorPickers).asColorPicker();
            }
        }).catch(error => console.error('Error loading jquery-asColorPicker:', error));
    }
}