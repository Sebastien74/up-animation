/**
 * Ajax row
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let beforeSend = function (row) {
        let spinnerIcon = row.querySelector('.fa-spin');
        if (spinnerIcon) {
            spinnerIcon.classList.remove('d-none');
        }
        let saveIcon = row.querySelector('.fa-save');
        if (saveIcon) {
            saveIcon.classList.add('d-none');
        }
        row.querySelectorAll('.form-control').forEach(function (input) {
            input.classList.remove('is-invalid');
            input.classList.remove('is-valid');
        });
        let invalids = row.querySelectorAll('.invalid-feedback');
        for (let j = 0; j < invalids.length; j++) {
            invalids[j].remove();
        }
    }

    let ajaxRow = function () {
        let ajaxSaveRows = document.querySelectorAll('.ajax-save-row');
        for (let i = 0; i < ajaxSaveRows.length; i++) {
            let btn = ajaxSaveRows[i];
            btn.onclick = function (event) {
                event.preventDefault();
                let row = btn.closest('tr');
                let form = document.getElementById(btn.dataset.formId);
                let refreshGroups = row.querySelectorAll('.refresh-group');
                let xHttp = new XMLHttpRequest()
                xHttp.open("POST", form.getAttribute('action') + '?ajax=true', true)
                xHttp.send(new FormData(form))
                beforeSend(row);
                xHttp.onload = function (e) {
                    if (this.readyState === 4 && this.status === 200) {
                        let response = this.response;
                        response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
                        response = JSON.parse(response);
                        response = response.html;
                        response = response.replace(/(<tr)/igm, '<div').replace(/<\/tr>/igm, '</div>');
                        response = response.replace(/(<td)/igm, '<div').replace(/<\/td>/igm, '</div>');
                        let html = document.createElement('div');
                        html.innerHTML = response;
                        refreshGroups.forEach(function (group) {
                            let input = group.querySelector('.form-control');
                            let groupHtml = html.querySelector('#' + group.getAttribute('id'));
                            let invalid = groupHtml.querySelector('.invalid-feedback');
                            if (invalid && group && input) {
                                group.append(invalid);
                                input.classList.add('is-invalid');
                            } else if (!invalid && input) {
                                input.classList.add('is-valid');
                            }
                        });
                    }
                    let spinnerIcon = row.querySelector('.fa-spin');
                    if (spinnerIcon) {
                        spinnerIcon.classList.add('d-none');
                    }
                    let saveIcon = row.querySelector('.fa-save');
                    if (saveIcon) {
                        saveIcon.classList.remove('d-none');
                    }
                    ajaxRow();
                }
            }
        }
    }
    ajaxRow();
}