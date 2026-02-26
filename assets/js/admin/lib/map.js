import 'select2';

import places from 'places.js';

let locale = document.querySelector('html').getAttribute('lang');
let inputPlaces = document.querySelectorAll('.input-places');

let countryEl = document.querySelector('select.country');
if (countryEl && typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
    jQuery(countryEl).select2();
}

if (inputPlaces.length > 0) {

    inputPlaces.forEach(input => {
        let placesAutocomplete = places({
            appId: 'plIZX27D5L3L',
            apiKey: '61cd64b7ddb5453f558240e9e5a17bc0',
            language: locale,
            container: document.querySelector('#' + input.getAttribute('id'))
        });

        placesAutocomplete.on('change', function (e) {

            const latitudeInput = document.querySelector('input.latitude');
            const longitudeInput = document.querySelector('input.longitude');
            const zipCodeInput = document.querySelector('input.zip-code');
            const departmentInput = document.querySelector('input.department');
            const regionInput = document.querySelector('input.region');
            const addressInput = document.querySelector('input.address');
            const cityInput = document.querySelector('input.city');

            if (latitudeInput) latitudeInput.value = e.suggestion.latlng.lat;
            if (longitudeInput) longitudeInput.value = e.suggestion.latlng.lng;
            if (zipCodeInput) zipCodeInput.value = e.suggestion.postcode;
            if (departmentInput) departmentInput.value = e.suggestion.county;
            if (regionInput) regionInput.value = e.suggestion.administrative;

            let address = e.suggestion.name ? e.suggestion.name : e.suggestion.value;
            if (addressInput) addressInput.value = address;

            let city = e.suggestion.city ? e.suggestion.city : e.suggestion.name;
            if (cityInput) cityInput.value = city;

            let country = e.suggestion.countryCode;
            if (countryEl) {
                countryEl.value = country.toUpperCase();
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
                    jQuery(countryEl).select2().trigger('change');
                } else {
                    countryEl.dispatchEvent(new Event('change'));
                }
            }
        });
    });
}