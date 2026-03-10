/**
 * Images cache clear
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

let buttonToClear = document.getElementById('clear-thumbs-btn');
let buttonGenerate = document.getElementById('generate-btn');
let loader = document.getElementById('main-preloader');

if (buttonToClear) {
    import('../../../scss/admin/lib/sweetalert.scss');

    let progressAction = function (progressCard, progressBar, counterWrap, progress, percent, filename = null) {
        if (filename) {
            progressCard.querySelector('.filename').innerText = filename;
        }
        progressBar.setAttribute('aria-valuenow', percent.toString());
        progressBar.style.width = percent + "%";
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
            fetch(thumb.dataset.url, {
                method: "DELETE"
            })
                .then(response => {
                    if (response.ok) {
                        thumb.remove();
                        let percent = (progress * 100) / thumbsLength;
                        progress++;
                        progressAction(progressCard, progressBar, counterWrap, progress, percent, filename);
                        clear(container, progress);
                    }
                });
        } else {
            progressAction(progressCard, progressBar, counterWrap, thumbsLength, 100);
            progressCard.classList.add('d-none');
            endProcessWrap.classList.remove('d-none');
            fetch(endProcessWrap.dataset.url, {
                method: "DELETE"
            })
                .then(response => {
                    if (response.ok) {
                        endProcessWrap.classList.add('d-none');
                        if (loader) {
                            loader.classList.add('d-none');
                        }
                    }
                });
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
            fetch(buttonToClear.dataset.url, {
                method: "DELETE"
            })
                .then(response => response.json())
                .then(response => {
                    let container = document.getElementById('medias-cache-clear-index');
                    container.innerHTML = response.html;
                    clear(container, 1);
                });
        });
    }
}