import 'select2';

import places from 'places.js';

let locale = $('html').attr('lang');
let inputPlaces = $('.input-places');

let countryEl = $('select.country');
if (countryEl.length > 0) {
    countryEl.select2();
}

if (inputPlaces.length > 0) {

    let placesAutocomplete = places({
        appId: 'plIZX27D5L3L',
        apiKey: '61cd64b7ddb5453f558240e9e5a17bc0',
        language: locale,
        container: document.querySelector('#' + inputPlaces.attr('id'))
    });

    placesAutocomplete.on('change', function (e) {

        $('input.latitude').val(e.suggestion.latlng.lat);
        $('input.longitude').val(e.suggestion.latlng.lng);
        $('input.zip-code').val(e.suggestion.postcode);
        $('input.department').val(e.suggestion.county);
        $('input.region').val(e.suggestion.administrative);

        let address = e.suggestion.name ? e.suggestion.name : e.suggestion.value;
        $('input.address').val(address);

        let city = e.suggestion.city ? e.suggestion.city : e.suggestion.name;
        $('input.city').val(city);

        let country = e.suggestion.countryCode;
        countryEl.val(country.toUpperCase());
        countryEl.select2().trigger('change');
    });
}