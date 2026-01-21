/**
 * Show password btn field
 */
export default function (showPasswordButtons) {
    for (let i = 0; i < showPasswordButtons.length; i++) {
        let btn = showPasswordButtons[i]
        btn.onclick = function (e) {
            btn.getElementsByClassName('show-icon')[0].classList.toggle('d-none')
            btn.getElementsByClassName('hide-icon')[0].classList.toggle('d-none')
            let input = btn.closest('.input-group').getElementsByClassName('form-control')[0]
            if (!input.classList.contains('show')) {
                input.classList.add('show')
                input.type = 'text'
            } else {
                input.classList.remove('show')
                input.type = 'password'
            }
        }
    }
}