/**
 * Form package
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 *  1 - On keyup on field
 *  2 - Scroll to errors
 *  3 - Datepicker
 *  4 - Selects
 *  5 - Prototype
 *  6 - Password field
 *  7 - Selects change event
 *  8 - Recaptcha
 *  9 - TouchSpin
 *  10 - File types
 */

import keyupFields from '../../../../vendor/components/keyup-fields';

export default function (scrollErrors = true) {

    /** 1 - On keyup on field */
    keyupFields()

    /** 2 - Scroll to errors */
    let errors = document.getElementsByClassName('invalid-feedback')
    if (errors && errors.length > 0 && scrollErrors) {
        import('../../../../vendor/components/scroll-error').then(({default: scrollErrors}) => {
            new scrollErrors()
        }).catch(error => console.error(error.message));
    }

    /** 3 - Datepicker */

    let inputPickers = document.querySelectorAll('input.datepicker')
    if (inputPickers.length > 0) {
        import('./datepicker').then(({default: datepicker}) => {
            new datepicker(inputPickers)
        }).catch(error => console.error(error.message));
    }

    let inputFlatPickers = document.querySelectorAll('input.flatpicker')
    if (inputFlatPickers.length > 0) {
        import('./flat-picker').then(({default: flatDatepicker}) => {
            new flatDatepicker(inputFlatPickers)
        }).catch(error => console.error(error.message));
    }

    let mcDatepickerEls = document.querySelectorAll('input.mc-datepicker')
    if (mcDatepickerEls.length > 0) {
        import('./mc-datepicker').then(({default: flatDatepicker}) => {
            new flatDatepicker(mcDatepickerEls)
        }).catch(error => console.error(error.message));
    }

    /** 4 - Selects */
    let selectors = document.querySelectorAll('.select-choice')
    if (selectors && selectors.length > 0) {
        import('../../../../vendor/plugins/choice').then(({default: choices}) => {
            new choices(selectors)
        }).catch(error => console.error(error.message));
    }

    /** 5 - Prototype */
    let prototypesBtn = document.querySelectorAll('.add-to-collection')
    if (prototypesBtn && prototypesBtn.length > 0) {
        import('./prototype').then(({default: prototype}) => {
            new prototype()
        }).catch(error => console.error(error.message));
    }

    /** 6 - Password field */
    let showPasswordButtons = document.getElementsByClassName('show-password')
    if (showPasswordButtons && showPasswordButtons.length > 0) {
        import('../../../../vendor/components/password-field').then(({default: passwords}) => {
            new passwords(showPasswordButtons)
        }).catch(error => console.error(error.message));
    }

    /** 7 - Selects change event */

    let isSelected = function (field) {
        let value = field.value
        let group = field.closest('.form-group')
        if (value) {
            field.classList.add('selected')
            if (group) {
                group.classList.add('selected')
            }
        } else {
            field.classList.remove('selected')
            if (group) {
                group.classList.remove('selected')
            }
        }
    }

    let selectElements = document.querySelectorAll('select')
    for (let i = 0; i < selectElements.length; i++) {
        let select = selectElements[i]
        isSelected(select)
        select.addEventListener('change', () => {
            isSelected(select)
        })
    }

    let btnCheckboxesGroups = document.getElementsByClassName('btn-group-toggle')
    for (let i = 0; i < btnCheckboxesGroups.length; i++) {
        let checkboxGroup = btnCheckboxesGroups[i]
        let label = checkboxGroup.querySelector('label')
        let input = checkboxGroup.querySelector('input')
        input.addEventListener('change', (event) => {
            label.classList.toggle('active')
            event.stopImmediatePropagation()
        })
    }

    /** 8 - Recaptcha */
    let formSecurity = document.querySelectorAll('form.security')
    if (formSecurity.length > 0) {
        import('../../../../vendor/components/recaptcha').then(({generate: Generate}) => {
            new Generate();
        }).catch(error => console.error(error.message));
    }

    // /** 9 - TouchSpin */
    // let inputs = document.querySelectorAll("input[type='number']")
    // if (inputs.length > 0) {
    //     import('../../../../vendor/plugins/touchspin').then(({default: touchspin}) => {
    //         new touchspin()
    // }).catch(error => console.error(error.message));
    // }

    /** 10 - File types */
    let inputsFile = document.querySelectorAll("input[type='file']");
    for (let i = 0; i < inputsFile.length; i++) {
        let inputFile = inputsFile[i];
        let wrap = inputFile.closest('.form-file-group');
        if (wrap) {
            inputFile.addEventListener('change', () => {
                let files = '';
                for (let j = 0; j < inputFile.files.length; j++) {
                    files = files + inputFile.files[j].name + '; ';
                }
                let preview = wrap.querySelector('.preview');
                if (preview) {
                    preview.classList.remove('d-none');
                    preview.classList.add('update');
                    preview.innerText = files.slice(0, -2);
                }
                let clearWrap = wrap.querySelector('.clear-wrap');
                if (clearWrap) {
                    clearWrap.classList.remove('d-none');
                    let clear = clearWrap.querySelector('.clear');
                    if (clear) {
                        clear.onclick = function () {
                            inputFile.value = null;
                            if (preview) {
                                preview.classList.add('d-none');
                                preview.innerText = preview.dataset.text;
                                preview.classList.remove('update');
                            }
                            clearWrap.classList.add('d-none');
                            let errors = wrap.querySelectorAll('.invalid-feedback');
                            errors.forEach(function (error) {
                                error.remove();
                            });
                        }
                    }
                }
            });
        }
    }

    // To set aria-invalid accessibility
    const invalidFields = document.querySelectorAll('input, select, textarea');
    invalidFields.forEach(field => {
        field.removeAttribute('aria-invalid');
        if (field.classList.contains('is-invalid')) {
            field.setAttribute('aria-invalid', 'true');
        }
    });

    // To include password checker
    document.querySelectorAll('.password-checker').forEach((input) => {
        import('../../../../vendor/components/password-checker').then(({default: Checker}) => {
            new Checker(input);
        }).catch(error => console.error(error.message));
    });
}