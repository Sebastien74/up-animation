import plugins from './plugins';

/**
 * Plugins
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 *  1 - Plugins
 *  2 - Popup
 *  3 - Lottie
 *  4 - Touch spin
 *  5 - Masonry
 *  6 - Dropify
 */

let body = document.body

/** 1 - Plugins */
plugins()

/** 2 - Popup */
let popupImages = body.querySelectorAll('.glightbox')
if (popupImages.length > 0) {
    import('./popup').then(({default: popup}) => {
        new popup();
    }).catch(error => console.error(error.message));
}

/** 3 - Lottie */
let icons = body.querySelectorAll('.ai')
if (icons.length > 0) {
    import('./lottie-icon').then(({default: lottiePlugin}) => {
        new lottiePlugin();
    }).catch(error => console.error(error.message));
}

/** 4 - Touch spin */
let inputs = body.querySelectorAll("input[type='number']")
if (inputs.length > 0) {
    import('./touchspin').then(({default: touchspin}) => {
        new touchspin(inputs);
    }).catch(error => console.error(error.message));
}

/** 5 - Masonry */
let columns = body.querySelectorAll('.grid-columns');
if (columns.length > 0) {
    import('./masonry').then(({default: masonry}) => {
        new masonry();
    }).catch(error => console.error(error.message));
}

/** 6 - Dropify */
let dropifyEls = body.querySelectorAll('input.dropify');
if (dropifyEls.length > 0) {
    import('./dropify').then(({default: dropify}) => {
        new dropify();
    }).catch(error => console.error(error.message));
}