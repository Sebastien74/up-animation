/**
 * Functions
 *
 * @copyright 2024
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 * @licence under the MIT License (LICENSE.txt)
 */

export function scrollToEL(el, middle = true, offset = 0) {
    const mainMenu = document.querySelector('.page-titles');
    const rect = el.getBoundingClientRect();
    const offsetTop = mainMenu ? mainMenu.getBoundingClientRect().height * 1.5 : 0;
    const elOffset = rect.top + window.scrollY;
    const elHeight = rect.height;
    const windowHeight = window.innerHeight;
    if (elHeight < windowHeight && middle) {
        offset = elOffset - ((windowHeight / 2) - (elHeight / 2));
    } else {
        offset = elOffset;
    }
    offset = offsetTop > 0 ? offset - offsetTop : elOffset;
    window.scrollTo({top: offset, behavior: 'smooth'});
}