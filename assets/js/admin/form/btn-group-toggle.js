/**
 * Button group toggle
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    let btnToggles = document.getElementsByClassName('btn-group-toggle');
    for (let i = 0; i < btnToggles.length; i++) {
        let btnToggle = btnToggles[i]
        btnToggle.onclick = function (e) {
            let input = btnToggle.querySelector('input');
            let label = btnToggle.querySelector('label');
            if (input.checked) {
                label.classList.add('active');
            } else {
                label.classList.remove('active');
            }
        }
    }
}