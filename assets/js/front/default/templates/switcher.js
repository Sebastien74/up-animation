/** Import CSS */
import '../../../../scss/front/default/templates/switcher.scss';

/** Import JS */

import Choices from "choices.js";

let trans = document.getElementById('data-translation')

const select = document.getElementById('users-front-switcher')
const choice = new Choices(select, {
    noResultsText: trans.getAttribute('data-choices-no-result'),
    itemSelectText: '',
    shouldSort: false,
    classNames: {
        containerOuter: 'choices-users-select'
    }
})

choice.passedElement.element.addEventListener('change', function (event) {
    window.location.replace(event.detail.value)
}, false)