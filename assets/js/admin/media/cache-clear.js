/**
 * Images cache clear
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import '../lib/sweetalert/sweetalert.min';
import '../../../scss/admin/lib/sweetalert.scss';

let buttonToClear = document.getElementById('clear-thumbs-btn');
let buttonGenerate = document.getElementById('generate-btn');
let loader = document.getElementById('main-preloader');

if (buttonToClear) {

    let progressAction = function (progressCard, progressBar, counterWrap, progress, percent, filename = null) {
        if (filename) {
            progressCard.querySelector('.filename').innerText = filename;
        }
        progressBar.setAttribute('aria-valuenow', percent.toString());
        progressBar.setAttribute('style', "width: " + percent + "%");
        counterWrap.innerHTML = progress.toString();
    }

    let clear = function (container, progress) {
        let thumb = container.querySelector('.thumb.to-clear');
        let indexWrap = document.getElementById('medias-cache-clear-index');
        let progressBar = indexWrap.querySelector('.progress-bar');
        let progressCard = indexWrap.querySelector('#progress-card');
        let endProcessWrap = indexWrap.querySelector('#end-process-wrap');
        let counterWrap = progressCard.querySelector('.count');
        let thumbsLength = parseInt(counterWrap.dataset.count);
        if (thumb) {
            let filename = thumb.dataset.filename;
            let xHttp = new XMLHttpRequest();
            xHttp.open("DELETE", thumb.dataset.url, true);
            xHttp.send();
            xHttp.onload = function (e) {
                if (this.readyState === 4 && this.status === 200) {
                    thumb.remove();
                    let percent = (progress * 100) / thumbsLength;
                    progress++;
                    progressAction(progressCard, progressBar, counterWrap, progress, percent, filename);
                    clear(container, progress);
                }
            }
        } else {
            progressAction(progressCard, progressBar, counterWrap, thumbsLength, 100);
            progressCard.classList.add('d-none');
            endProcessWrap.classList.remove('d-none');
            let xHttp = new XMLHttpRequest();
            xHttp.open("DELETE", endProcessWrap.dataset.url, true);
            xHttp.send();
            xHttp.onload = function (e) {
                if (this.readyState === 4 && this.status === 200) {
                    endProcessWrap.classList.add('d-none');
                    if (loader) {
                        loader.classList.add('d-none');
                    }
                }
            }
        }
    }

    buttonToClear.onclick = function () {
        let trans = document.getElementById('data-translation');
        return swal({
            title: trans.dataset.swalDeleteTitle,
            text: trans.dataset.swalDeleteText,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: trans.dataset.swalDeleteConfirmText,
            cancelButtonText: trans.dataset.swalDeleteCancelText,
            closeOnConfirm: true
        }, function () {
            if (loader) {
                loader.classList.remove('d-none');
            }
            if (buttonGenerate) {
                buttonGenerate.remove();
            }
            buttonToClear.remove();
            let xHttp = new XMLHttpRequest();
            xHttp.open("DELETE", buttonToClear.dataset.url, true);
            xHttp.send();
            xHttp.onload = function (e) {
                if (this.readyState === 4 && this.status === 200) {
                    let response = this.response;
                    response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
                    response = JSON.parse(response);
                    let container = document.getElementById('medias-cache-clear-index');
                    container.innerHTML = response.html;
                    clear(container, 1);
                }
            }
        });
    }
}