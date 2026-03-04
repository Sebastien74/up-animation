/**
 * Button group toggle
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    document.querySelectorAll('.btn-group-toggle').forEach(btnToggle => {
        btnToggle.addEventListener('click', function (e) {
            const label = btnToggle.querySelector('label');
            const input = document.getElementById(label.getAttribute('for'));
            if (input.checked) {
                label.classList.add('active');
            } else {
                label.classList.remove('active');
            }
            e.stopImmediatePropagation();
        });
    });
}