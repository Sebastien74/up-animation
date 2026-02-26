import '../lib/sweetalert/sweetalert.min';

export default function () {

    swal.close();

    let btn = document.getElementById('reorder-medias-btn');

    let preloader = document.getElementById('main-preloader');
    if (preloader) {
        preloader.classList.remove('d-none');
    }

    let importData = function (progress) {
        let list = document.getElementById('medias-to-reorder');
        let item = list.querySelector('.item.to-reorder');
        let progressCard = document.getElementById('progress-card');
        let progressBar = progressCard.querySelector('.progress-bar');
        let successCard = document.getElementById('success-card');
        let counterWrap = progressCard.querySelector('.count');
        let nameWrap = progressCard.querySelector('.name');
        let itemsLength = parseInt(counterWrap.dataset.count);
        if (item) {
            fetch(item.dataset.path, {
                method: "GET"
            })
                .then(response => {
                    if (response.ok) {
                        nameWrap.innerHTML = item.dataset.name;
                        item.remove();
                        let percent = (progress * 100) / itemsLength;
                        progressBar.setAttribute('aria-valuenow', percent.toString());
                        progressBar.style.width = percent + "%";
                        counterWrap.innerHTML = progress.toString();
                        progress++;
                        importData(progress);
                    }
                });
        } else {
            setTimeout(function () {
                progressCard.remove();
                successCard.classList.remove('d-none');
                window.location.replace(window.location.href);
            }, 1500);
        }
    }

    fetch(btn.dataset.path, {
        method: "GET"
    })
        .then(response => response.json())
        .then(response => {
            let importWrap = document.getElementById('ajax-reorder-wrap');
            importWrap.innerHTML = response.html;
            importWrap.classList.remove('d-none');
            importData(1);
        });
}