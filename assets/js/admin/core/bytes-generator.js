/**
 * Bytes generator
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (event, el, tokenLength = 30) {

    let spinnerIcon = $(el).find('svg');
    let group = $(el).closest('.form-group');
    let input = group.find('input');

    group.removeClass('is-invalid');
    group.find('.invalid-feedback').remove();
    input.removeClass('is-invalid');

    spinnerIcon.toggleClass('fa-spin');

    const rand = () => Math.random().toString(36).substr(2);
    const token = (length) => (rand() + rand() + rand() + rand()).substr(0, length);

    input.val(token(tokenLength));

    spinnerIcon.toggleClass('fa-spin');

    event.stopImmediatePropagation();
    return false;
}