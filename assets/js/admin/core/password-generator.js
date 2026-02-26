import route from "../../vendor/components/routing";

/**
 * Password generator
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (event, el) {

    let spinnerIcon = el.querySelector('svg');
    let referCopy = document.body.querySelector('.refer-copy');

    if (referCopy && !referCopy.classList.contains('d-none')) {
        referCopy.classList.add('d-none');
    }
    if (spinnerIcon) spinnerIcon.classList.add('fa-spin');

    fetch(route('security_password_generator'), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(response => {
            if (response.password) {
                if (referCopy) referCopy.classList.remove('d-none');
                const toCopy = document.body.querySelector('.to-copy');
                if (toCopy) toCopy.textContent = response.password;
                if (spinnerIcon) spinnerIcon.classList.remove('fa-spin');
            }
        })
        .catch(errors => {
            /** Display errors */
            import('../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
        });

    event.stopImmediatePropagation();
    return false;
}