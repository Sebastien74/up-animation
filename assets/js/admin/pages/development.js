import '../../../scss/admin/pages/development.scss';

// import places from 'places.js';

// let locale = $('html').attr('lang');

$.fn.simulateKeyPress = function (character) {
    $(this).trigger({type: 'keypress', which: character.charCodeAt(0)});
};

let importData = function (progress) {
    let indexLinks = document.getElementById('import-index-links');
    let index = document.getElementById('index-import-data');
    let list = document.getElementById('entities-to-import');
    let item = list.querySelector('.item.to-import');
    let progressCard = document.getElementById('progress-card');
    let progressBar = progressCard.querySelector('.progress-bar');
    let successCard = document.getElementById('success-card');
    let counterWrap = progressCard.querySelector('.count');
    let nameWrap = progressCard.querySelector('.name');
    let itemsLength = parseInt(counterWrap.dataset.count);
    if (!indexLinks.classList.contains('d-none')) {
        indexLinks.classList.add('d-none');
    }
    if (item) {
        let xHttp = new XMLHttpRequest()
        xHttp.open("GET", item.dataset.path, true)
        xHttp.send()
        xHttp.onload = function (e) {
            if (this.readyState === 4 && this.status === 200) {
                nameWrap.innerHTML = item.dataset.name;
                item.remove();
                let percent = (progress * 100) / itemsLength;
                progressBar.setAttribute('aria-valuenow', percent.toString());
                progressBar.setAttribute('style', "width: " + percent + "%");
                counterWrap.innerHTML = progress.toString();
                progress++;
                importData(progress);
            }
        }
    } else {
        setTimeout(function () {
            progressCard.remove();
            successCard.classList.remove('d-none');
            setTimeout(function () {
                index.remove();
                indexLinks.classList.remove('d-none');
            }, 3000);
        }, 1500);
    }
}

let importBoutons = document.querySelectorAll('.import-data-btn');
importBoutons.forEach(function (btn) {
    btn.onclick = function () {
        let xHttp = new XMLHttpRequest();
        xHttp.open("GET", btn.dataset.path, true);
        xHttp.send();
        xHttp.onload = function (e) {
            if (this.readyState === 4 && this.status === 200) {
                let response = JSON.parse(this.response);
                let importWrap = document.getElementById('ajax-import-wrap');
                importWrap.innerHTML = response.html;
                importWrap.classList.remove('d-none');
                importData(1);
            }
        }
    }
});

// $('#cities-bio').find('.bio-places').each(function () {
//
//     let el = $(this);
//     let data = $(this);
//     // let input = $('#bio-places');
//     // setTimeout(function () {
//     //     console.log(el);
//     //     input.val(el.data('city') + ' ' + el.data('zipcode'));
//     //     $('body').simulateKeyPress('x');
//     // }, 3000);
//
//     let placesAutocomplete = places({
//         appId: 'plIZX27D5L3L',
//         apiKey: '61cd64b7ddb5453f558240e9e5a17bc0',
//         language: locale,
//         type: 'townhall',
//         container: document.querySelector('#' + el.attr('id'))
//     });
//
//     el.val(el.data('city'));
//
//     placesAutocomplete.on('change', function (e) {
//
//         console.log(e);
//         // $('input.latitude').val(e.suggestion.latlng.lat);
//         // $('input.longitude').val(e.suggestion.latlng.lng);
//         // $('input.zip-code').val(e.suggestion.postcode);
//         // $('input.department').val(e.suggestion.county);
//         // $('input.region').val(e.suggestion.administrative);
//         //
//         // let address = e.suggestion.name ? e.suggestion.name : e.suggestion.value;
//         // $('input.address').val(address);
//         //
//         // let city = e.suggestion.city ? e.suggestion.city : e.suggestion.name;
//         // $('input.city').val(city);
//         //
//         // let country = e.suggestion.countryCode;
//         // countryEl.val(country.toUpperCase());
//         // countryEl.select2().trigger('change');
//     });
// });