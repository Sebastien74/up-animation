import generator from '../core/code-generator'

/**
 * Tab
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    let el = $(this);
    let body = $(this);
    let haveSlugField = body.find('.tab-pane').find("input[code='code']");
    body.find('.nav-link').removeClass('is-current');
    el.addClass('is-current');
    if(haveSlugField.length > 0 && !el.hasClass('is-config')) {
        el.addClass('is-config');
        generator();
    }
}