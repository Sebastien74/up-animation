import '../../../../../scss/front/default/components/form/_form-calendar.scss';

const Choices = require("choices.js")

/**
 * Form calendar
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (event = null, referForm = null) {

    let formContainer = document.querySelectorAll('.form-container')
    let trans = document.getElementById('data-translation')

    let registerForm = null
    let formCalendar = null
    if (formContainer.length > 0) {
        registerForm = formContainer[0].querySelector('form')
        formCalendar = formContainer[0].querySelector('form.form-calendar')
    }


    /** To get data-href element */
    let getHref = function (el) {
        let href = typeof el.dataset.href != 'undefined' ? el.dataset.href : el.getAttribute('value')
        let url = href + '?ajax=true';
        if (href.indexOf('?') > -1) {
            url = href + '&ajax=true'
        }
        return url
    }

    /** To initialize calendars select element */
    let calendarsSelect = function () {

        let select = document.getElementById('calendars-selector')

        const choice = new Choices(select, {
            noResultsText: trans.getAttribute('data-choices-no-result'),
            itemSelectText: '',
            shouldSort: false,
            classNames: {
                containerOuter: 'calendars-selector-group'
            }
        })

        /** On calendars selector change */
        choice.passedElement.element.addEventListener('change', function (event) {
            let el = select.options[select.selectedIndex]
            let loader = el.closest('.form-calendar-container').querySelector('.card-loader')
            refreshCalendar(event, loader, el)
        }, false)
    }
    calendarsSelect()

    /** To set calendar Id in form registration action */
    if (registerForm && formCalendar) {
        registerForm.setAttribute('action', registerForm.getAttribute('data-action') + '?calendar=' + formCalendar.dataset('calendar'));
    }

    /** To refresh calendar */
    let refreshCalendar = function (e, loader, el = null, path = null) {

        loader.classList.toggle('d-none')

        let url = path ? path : getHref(el)

        let xHttp = new XMLHttpRequest()
        xHttp.open("GET", url, true)
        xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8")
        xHttp.send()
        xHttp.onload = function (e) {
            if (this.readyState === 4 && this.status === 200) {

                let response = JSON.parse(this.response)

                if (response.html) {

                    let htmlEl = document.createElement('div')
                    htmlEl.innerHTML = response.html
                    let ajaxContent = document.getElementsByClassName("form-calendar-ajax-content")[0]
                    ajaxContent.innerHTML = htmlEl.getElementsByClassName("form-calendar-ajax-content")[0].innerHTML

                    controlsEvent()
                    inputsSlotsEvent()
                    formSubmit()
                    calendarsSelect()
                }

                if (response.calendar && registerForm) {
                    registerForm.setAttribute('action', registerForm.getAttribute('data-action') + '?calendar=' + response.calendar)
                }
            }
        }
    }

    /** On click controls */
    let controlsEvent = function () {
        let controls = document.querySelectorAll('.form-calendar-container .change-dates-btn')
        for (let i = 0; i < controls.length; i++) {
            let el = controls[i]
            el.onclick = function (e) {
                e.preventDefault()
                let loader = el.closest('.card').querySelector('.card-loader')
                refreshCalendar(e, loader, el)
            }
        }
    }
    controlsEvent()

    /** On form registration submit */
    if (event && referForm) {
        let token = referForm.getAttribute('data-custom-id')
        if (typeof token != 'undefined') {
            let form = document.querySelector('[data-component="form-calendar"]')
            let url = form.getAttribute('action') + '?website=' + document.body.dataset.id + '&token=' + token + '&calendar=' + form.getAttribute('calendar') + '&ajax=true'
            let loader = document.querySelector('.form-calendar-container').querySelector('.card-loader')
            history.pushState({}, null, '?token=' + token + '&calendar=' + form.dataset.calendar);
            refreshCalendar(event, loader, null, url);
        }
    }

    /** On change input block slot */
    let inputsSlotsEvent = function () {

        let inputsSlots = document.querySelectorAll('.btn-block-slot input')
        for (let i = 0; i < inputsSlots.length; i++) {

            let input = inputsSlots[i]

            input.addEventListener('change', (e) => {

                let btn = input.closest('.btn-block-slot')
                let form = input.closest('form')

                if (form.classList.contains('disabled')) {
                    e.preventDefault()
                    return false
                }

                if (btn.classList.contains('available')) {

                    let inputId = input.getAttribute('id')
                    let blocksSlots = document.getElementsByClassName('btn-block-slot')

                    for (let j = 0; j < blocksSlots.length; j++) {

                        let slot = blocksSlots[j]
                        let slotInput = slot.querySelector('input')

                        if (slotInput) {

                            let slotId = slotInput.getAttribute('id')
                            let isDisabled = slot.classList.contains('unavailable')
                            let isActive = slot.classList.contains('active')

                            if (!isDisabled && slotId !== inputId) {
                                slot.classList.remove('active')
                            } else if (!isDisabled && slotId === inputId && !isActive) {
                                slot.classList.add('active')
                                let submitBtn = document.getElementById('block-slot-submit')
                                if (submitBtn.classList.contains('disabled')) {
                                    submitBtn.classList.remove('disabled')
                                }
                            }
                        }
                    }
                }
            })
        }
    }
    inputsSlotsEvent()

    /** On submit block slot */
    let formSubmit = function () {

        let submitBtn = document.querySelector('.form-calendar-container #block-slot-submit')

        if(submitBtn) {

            submitBtn.onclick = function (e) {

                e.preventDefault()

                if (!submitBtn.classList.contains('disabled')) {

                    let form = submitBtn.closest('form')
                    let loader = form.querySelector('.card-loader');

                    loader.classList.toggle('d-none')

                    let xHttp = new XMLHttpRequest()
                    xHttp.open("POST", form.getAttribute('action') + "&ajax=true", true)
                    xHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded")
                    xHttp.send(serialize(form))
                    xHttp.onload = function (e) {
                        if (this.readyState === 4 && this.status === 200) {
                            let response = JSON.parse(this.response)
                            if (response.html) {
                                let htmlEl = document.createElement('div')
                                htmlEl.innerHTML = response.html
                                let ajaxContent = document.getElementsByClassName("form-calendar-ajax-content")[0]
                                ajaxContent.innerHTML = htmlEl.getElementsByClassName("form-calendar-ajax-content")[0].innerHTML
                            }
                        }
                    }
                }
            }
        }
    }
    formSubmit()

    /** Serialize form data */
    let serialize = function (form) {
        let serialized = []
        for (let i = 0; i < form.elements.length; i++) {
            let field = form.elements[i]
            if (!field.name || field.disabled || field.type === 'file' || field.type === 'reset' || field.type === 'submit' || field.type === 'button') continue
            if (field.type === 'select-multiple') {
                for (let n = 0; n < field.options.length; n++) {
                    if (!field.options[n].selected) continue
                    serialized.push(encodeURIComponent(field.name) + "=" + encodeURIComponent(field.options[n].value))
                }
            } else if ((field.type !== 'checkbox' && field.type !== 'radio') || field.checked) {
                serialized.push(encodeURIComponent(field.name) + "=" + encodeURIComponent(field.value))
            }
        }
        return serialized.join('&')
    }
}