/**
 * Autocomplete
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import autocomplete from 'autocomplete.js/dist/autocomplete.jquery.min';

export default function () {

    let el = document.querySelectorAll('.js-autocomplete');

    if (el.length > 0) {

        el.forEach(function (element) {

            let autocompleteUrl = element.dataset.autocompleteUrl;
            let autocompleteKey = element.dataset.autocompleteKey;

            if (typeof jQuery !== 'undefined' && typeof jQuery.fn.autocomplete !== 'undefined') {
                jQuery(element).autocomplete({hint: false}, [
                    {
                        source: function (query, response) {
                            fetch(autocompleteUrl + '?query=' + query, {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                                .then(res => res.json())
                                .then(data => {
                                    response(data);
                                })
                                .catch(err => console.error(err));
                        },
                        displayKey: autocompleteKey,
                        debounce: 500 // only request every 1/2 second
                    }
                ]);
            }
        });
    }
};