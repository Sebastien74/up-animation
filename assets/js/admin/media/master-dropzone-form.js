import {AlertHTML} from '../functions';

/**
 * Send a master form on Dropzone process
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let masterForm = document.body.querySelector('.master-dropzone-form');

    if (masterForm && !masterForm.classList.contains('is-submit')) {

        let masterFormId = masterForm.getAttribute('id');
        let formData = new FormData(document.getElementById(masterFormId));
        masterForm.classList.add('is-submit');

        fetch(masterForm.getAttribute('action') + "?ajax=true", {
            method: "POST",
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw text; });
                }
                return response.json();
            })
            .then(response => {
                // Success
            })
            .catch(error => {
                displayErrors(error);
            });
    }

    function displayErrors(errors) {
        let dropzoneErrorsEl = document.getElementById('dropzone-errors');
        let errorsEl = dropzoneErrorsEl ? dropzoneErrorsEl : document.getElementById('admin-body');
        if (errorsEl) {
            errorsEl.insertAdjacentHTML('afterbegin', AlertHTML(errors));
        }
    }
}