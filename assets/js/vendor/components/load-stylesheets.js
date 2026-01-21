/**
 * To load preload stylesheet resources
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (href, onload) {

    let usedLaterStylesheet = document.createElement('link');
    let rel = onload ? "preload" : "stylesheet";

    if (onload) {
        usedLaterStylesheet.setAttribute("as", "style");
    }

    usedLaterStylesheet.setAttribute("crossorigin", "anonymous");
    usedLaterStylesheet.setAttribute("rel", rel);
    usedLaterStylesheet.setAttribute("href", href);
    document.head.appendChild(usedLaterStylesheet);
}