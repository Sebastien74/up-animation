/**
 * Choices
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @inheritDoc https://github.com/jshjohnson/Choices
 */

import Choices from "choices.js";
import "choices.js/public/assets/styles/choices.css";

export default function (selectors, returnElem = false) {

    let displayClear = function (selector, change) {
        let formGroup = selector.closest('.form-group')
        let value = selector.value
        if (formGroup) {
            let clearBtn = formGroup ? formGroup.querySelector('.choices__button') : null
            if (clearBtn && value) {
                clearBtn.classList.add('show')
            } else if (clearBtn && change) {
                clearBtn.classList.remove('show')
            }
        }
    }

    const trans = document.getElementById('data-translation');

    selectors.forEach(function (selector) {

        let searchTrans = trans.dataset.hasOwnProperty('search') ? selector.dataset.search : 'Rechercher';
        let searchPlaceholderValue = selector.dataset.hasOwnProperty('searchPlaceholder') ? selector.dataset.searchPlaceholder : searchTrans;
        let placeholderValue = selector.getAttribute('placeholder') ? selector.getAttribute('placeholder') : '';
        let noChoicesText = selector.getAttribute('noChoicesText') ? selector.getAttribute('noChoicesText') : '';
        let removeBtn = parseInt(selector.dataset.remove) === 1

        const choice = new Choices(selector, {
            searchEnabled: true,
            searchChoices: true,
            choices: [],
            placeholderValue: placeholderValue,
            noResultsText: trans.getAttribute('data-choices-no-result'),
            itemSelectText: '',
            noChoicesText: noChoicesText,
            removeItems: false,
            removeItemButton: false,
            searchPlaceholderValue: searchPlaceholderValue,
            shouldSort: false,
            // classNames: {
            //     containerOuter: ['selector-group', 'w-100']
            // },
            callbackOnInit: function (ev) {
                if (removeBtn) {
                    displayClear(selector)
                }
            }
        });

        choice.passedElement.element.addEventListener('showDropdown', function () {
            let invalidGroup = selector.closest('.form-group.is-invalid')
            if (invalidGroup) {
                invalidGroup.classList.remove('is-invalid');
                invalidGroup.querySelectorAll('.invalid-feedback').forEach(function (feedback) {
                    feedback.remove()
                });
            }
        }, false);

        choice.passedElement.element.addEventListener('change', () => {
            if (removeBtn) {
                displayClear(selector, true)
            }
        }, false);

        if (returnElem) {
            return choice;
        }
    });
}
