/**
 * Remove form errors
 */
export default function () {

    function removeElementsByClass(className) {
        const elements = document.getElementsByClassName(className);
        while (elements.length > 0) {
            elements[0].parentNode.removeChild(elements[0]);
        }
    }

    let feedbacks = document.getElementsByClassName('invalid-feedback')
    if (feedbacks.length > 0) {
        removeElementsByClass('invalid-feedback')
    }

    let invalidDivs = document.querySelectorAll('div.is-invalid')
    if (invalidDivs.length > 0) {
        for (let i = 0; i < invalidDivs.length; i++) {
            invalidDivs[i].classList.remove('is-invalid')
        }
    }

    let invalidFields = document.querySelectorAll('.form-control.is-invalid')
    if (invalidFields.length > 0) {
        for (let i = 0; i < invalidFields.length; i++) {
            invalidFields[i].classList.remove('is-invalid')
        }
    }

    let invalidCheckboxes = document.querySelectorAll('.form-check-input')
    if (invalidCheckboxes.length > 0) {
        for (let i = 0; i < invalidCheckboxes.length; i++) {
            invalidCheckboxes[i].classList.remove('is-invalid')
        }
    }

    let invalidEls = document.getElementsByClassName('is-invalid')
    if (invalidEls.length > 0) {
        for (let i = 0; i < invalidEls.length; i++) {
            invalidEls[i].classList.remove('is-invalid')
        }
    }

    /** On keyup on field */
    import('./keyup-fields').then(({default: keyupFields}) => {
        new keyupFields()
    }).catch(error => console.error(error.message));
}