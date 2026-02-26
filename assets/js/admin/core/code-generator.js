import route from "../../vendor/components/routing";

/**
 * Code generator
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let generateLinks = document.querySelectorAll('.generate-code')
    for (let i = 0; i < generateLinks.length; i++) {
        let link = generateLinks[i]
        link.onclick = function (e) {
            generate(e, link)
            e.preventDefault()
        }
    }

    let generate = function (event, el) {

        let form = el.closest('form');
        let prototype = el.closest('.prototype');
        let referGroup = prototype ? prototype : form;
        let referElAdminName = false;
        referGroup.querySelectorAll('.refer-code.admin-name').forEach((el) => {
            if (el.value) {
                referElAdminName = el;
            }
        });
        let referEl = referGroup.querySelector('.refer-code');
        let referVal = referElAdminName ? referElAdminName.value : (referEl ? referEl.value : '');
        let referName = referVal ? referVal.replace(/[/]/g, '-') : 'undefined';
        let spinnerIcon = el.querySelector('svg') ? el.querySelector('svg') : el.querySelector('i');
        let inModal = el.closest('.modal');
        let url = route('admin_code_generator', {
            url: el.dataset.urlId,
            classname: el.dataset.classname,
            entityId: el.dataset.entityId,
            string: encodeURI(referName),
        });

        spinnerIcon.classList.toggle('fa-spin');

        fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                throw new Error('Network response was not ok.');
            })
            .then(response => {
                if (response.code && response.code !== 'undefined') {
                    if (el.classList.contains('has-code') || inModal) {
                        el.previousElementSibling.value = response.code;
                    } else {
                        const urlEditGroup = el.closest('.url-edit-group');
                        if (urlEditGroup) {
                            const inputCode = urlEditGroup.querySelector("input[code='code']");
                            if (inputCode) inputCode.value = response.code;
                        }
                    }
                }
                if (spinnerIcon) spinnerIcon.classList.toggle('fa-spin');
            })
            .catch(error => {
                console.error('Error:', error);
                if (spinnerIcon) spinnerIcon.classList.toggle('fa-spin');
            });
        event.stopImmediatePropagation();
        return false;
    }
}