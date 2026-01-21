/**
 * Images cache resolve
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

let buttonToGenerate = document.getElementById('generate-btn')

if (buttonToGenerate) {

    buttonToGenerate.onclick = function () {

        let preloader = document.getElementById('main-preloader')
        let container = document.getElementById('medias-cache-index')
        let progressCard = document.getElementById('progress-card')
        let successCard = document.getElementById('success-card')
        let progressBar = progressCard.querySelector('.progress-bar')
        let counterWrap = progressCard.querySelector('.count')
        let thumbsLength = parseInt(counterWrap.dataset.count)
        let filenameWrap = progressCard.querySelector('.filename')
        let thumbWrap = progressCard.querySelector('.thumb')
        let progress = 1

        if (preloader) {
            preloader.classList.toggle('d-none')
        }

        container.classList.remove('pt-5')
        container.classList.remove('pb-5')
        container.classList.add('pt-0')
        container.classList.add('pb-2')
        buttonToGenerate.classList.toggle('d-none')
        progressCard.classList.toggle('d-none')

        let generate = function () {
            let thumb = progressCard.querySelector('.thumb.to-generate')
            let path = thumb.closest('.filename')
            let xHttp = new XMLHttpRequest()
            xHttp.open("GET", thumb.dataset.url, true)
            xHttp.send()
            xHttp.onload = function (e) {
                if (this.readyState === 4 && this.status === 200) {
                    filenameWrap.innerHTML = path.dataset.filename
                    thumbWrap.innerHTML = thumb.dataset.name
                    thumb.remove()
                    let percent = (progress * 100) / thumbsLength
                    progressBar.setAttribute('aria-valuenow', percent.toString())
                    progressBar.setAttribute('style', "width: " + percent + "%")
                    counterWrap.innerHTML = progress.toString()
                    progress++
                    if (progress === (thumbsLength + 1)) {
                        setTimeout(function () {
                            progressCard.classList.add('d-none')
                            successCard.classList.remove('d-none')
                            setTimeout(function () {
                                successCard.classList.add('d-none')
                                if (preloader) {
                                    preloader.classList.toggle('d-none')
                                }
                            }, 1500)
                        }, 1000)
                    } else {
                        generate();
                    }
                }
            }
        }
        generate();
    }
}