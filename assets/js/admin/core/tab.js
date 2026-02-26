import generator from '../core/code-generator'

/**
 * Tab
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    let el = this;
    let body = this;
    let haveSlugField = body.querySelector(".tab-pane input[code='code']");
    body.querySelectorAll('.nav-link').forEach(link => link.classList.remove('is-current'));
    el.classList.add('is-current');
    if (haveSlugField && !el.classList.contains('is-config')) {
        el.classList.add('is-config');
        generator();
    }
}