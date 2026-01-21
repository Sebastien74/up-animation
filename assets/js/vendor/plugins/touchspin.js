import '../../admin/lib/jquery.bootstrap-touchspin';

/**
 * Touchspin
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    let inputs = document.querySelectorAll("input[type='number']")
    for (let i = 0; i < inputs.length; i++) {
        let input = inputs[i]
        $(input).TouchSpin({
            min: input.getAttribute('min') ? input.getAttribute('min') : 0,
            max: input.getAttribute('max') ? input.getAttribute('max') : 1000000000
        })
    }
}