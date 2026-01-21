/**
 * Scroll to
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (el, middle = true) {
    let elOffset = el.getBoundingClientRect().top + window.scrollY;
    let elHeight = el.offsetHeight;
    let windowHeight = window.innerHeight;
    let offset;
    if (elHeight < windowHeight && middle) {
        offset = elOffset - ((windowHeight / 2) - (elHeight / 2));
    } else {
        offset = elOffset;
    }
    window.scrollTo({top: offset, behavior: 'smooth'});
}