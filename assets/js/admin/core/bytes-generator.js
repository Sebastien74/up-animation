/**
 * Bytes generator
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (event, el, tokenLength = 30) {

    let spinnerIcon = el.querySelector('svg');
    let group = el.closest('.form-group') ? el.closest('.form-group') : el.closest('.group-form');
    let input = group ? group.querySelector('input') : null;

    if (group) {
        group.classList.remove('is-invalid');
        group.querySelectorAll('.invalid-feedback').forEach(err => err.remove());
    }
    if (input) {
        input.classList.remove('is-invalid');
    }

    if (spinnerIcon) spinnerIcon.classList.toggle('fa-spin');

    const rand = () => Math.random().toString(36).slice(2);
    const token = (length) => (rand() + rand() + rand() + rand()).slice(0, length);

    if (input) {
        input.value = token(tokenLength);
    }

    if (spinnerIcon) spinnerIcon.classList.toggle('fa-spin');

    event.stopImmediatePropagation();
    return false;
}