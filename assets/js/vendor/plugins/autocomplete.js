/**
 * Autocomplete
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import autocomplete from 'autocomplete.js/dist/autocomplete.jquery.min';

export default function () {

    let el = $('.js-autocomplete');

    if (el.length > 0) {

        el.each(function () {

            let el = $(this);
            let autocompleteUrl = el.data('autocomplete-url');
            let autocompleteKey = el.data('autocomplete-key');

            el.autocomplete({hint: false}, [
                {
                    source: function (query, response) {
                        $.ajax({
                            url: autocompleteUrl + '?query=' + query,
                            type: "GET",
                            dataType: 'json',
                            // data: {
                            //     term: request.term
                            // },
                            success: function (data) {
                                response(data);
                            }
                        });
                    },
                    displayKey: autocompleteKey,
                    debounce: 500 // only request every 1/2 second
                }
            ])
        });
    }
};