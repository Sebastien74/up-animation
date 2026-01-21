import '../plugins/material-datetimepicker';
import '../../../scss/admin/lib/material-datetimepicker.scss';
import '../../../lib/fonts/material.scss';

/**
 * Date Picker
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let trans = $('#data-translation');

    $('.datepicker').bootstrapMaterialDatePicker({
        minDate: null, /** new Date(datepicker.val()) */
        maxDate: null,
        currentDate: null,
        date: true,
        disabledDays: [],
        format: 'DD/MM/YYYY',
        shortTime: true,
        weekStart: 0,
        nowButton: false,
        cancelText: trans.data('date-picker-close'),
        clearText: trans.data('date-picker-clear'),
        nowText: trans.data('date-picker-now'),
        okText: trans.data('date-picker-validate'),
        switchOnClick: false,
        triggerEvent: 'focus',
        time: false,
        lang: $('html').attr('lang'),
        monthPicker: false,
        year: true
    }).change(function (event, date) {});
}