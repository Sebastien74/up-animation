/**
 * Recaptcha
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export function generate() {

    let dataEl = document.getElementById('data-path');

    let recaptcha = function () {

        let body = document.body;

        let recaptchaEl = document.getElementById('recaptcha');
        if (recaptchaEl) {
            if (recaptchaEl.classList.contains('d-none')) {
                recaptchaEl.classList.remove('d-none');
            }
            recaptchaEl.onclick = function () {
                if (!recaptchaEl.classList.contains('active')) {
                    recaptchaEl.classList.add('active');
                }
            }
            recaptchaEl.addEventListener('mouseleave', e => {
                if (recaptchaEl.classList.contains('active')) {
                    recaptchaEl.classList.remove('active');
                }
            })
        }

        body.querySelectorAll('form.security').forEach(function (form) {
            let data = form.querySelector('.form-data');
            let string = encodeURIComponent(data.dataset.id);
            let website = data.dataset.website;
            if (string !== '' && website !== '') {
                let xHttp = new XMLHttpRequest();
                let url = dataEl.dataset.encrypt + '/' + website + '/' + string;
                xHttp.open("GET", url, true);
                xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
                xHttp.send();
                xHttp.onload = function (e) {
                    if (this.readyState === 4 && this.status === 200) {
                        let response = JSON.parse(this.response);
                        if (response.result !== false) {
                            let field = form.querySelector('.field_ho');
                            field.dataset.honey = response.result;
                        }
                    }
                }
            }
        });
    }

    recaptcha();
}

export function onSubmit(form) {
    let honeyField = form.querySelector('.field_ho');
    if (honeyField) {
        honeyField.style.position = 'initial';
        honeyField.style.left = 'initial';
        honeyField.type = 'hidden';
        if (!honeyField.value) {
            honeyField.value = honeyField.dataset.honey;
        }
    }
}