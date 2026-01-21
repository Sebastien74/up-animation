/** https://mcdatepicker.netlify.app/docs/ */

import MCDatepicker from 'mc-datepicker';
import "../../../scss/admin/lib/_mc-calendar.scss";
import moment from "moment";

export default function (pickers) {

    let trans = document.getElementById('data-translation');
    let localeSplit = document.documentElement.getAttribute('lang').split('_');
    let locale = localeSplit.shift();

    moment.locale(locale);

    pickers.forEach(picker => {
        let id = picker.getAttribute('id');
        const datePicker = MCDatepicker.create({
            el: '#' + id,
            bodyType: 'modal',
            customWeekDays: moment.weekdays(),
            customMonths: moment.months(),
            customOkBTN: trans.dataset.ok,
            customCancelBTN: trans.dataset.close,
            customClearBTN: trans.dataset.deselect,
            dateFormat: trans.dataset.formatDate,
            autoClose: true,
            closeOnBlur: true,
            theme: {
                theme_color: '#240618'
            }
        });
    });
}