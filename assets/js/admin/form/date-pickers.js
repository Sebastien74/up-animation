import '../plugins/material-datetimepicker';
import '../../../scss/admin/lib/material-datetimepicker.scss';
import '../../../lib/fonts/material.scss';

/**
 * Date Picker
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let trans = document.getElementById('data-translation');
    let lang = document.querySelector('html').getAttribute('lang');

    let datepickers = document.querySelectorAll('.datepicker');
    datepickers.forEach(function (datepicker) {
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.bootstrapMaterialDatePicker !== 'undefined') {
            jQuery(datepicker).bootstrapMaterialDatePicker({
                minDate: null, /** new Date(datepicker.val()) */
                maxDate: null,
                currentDate: null,
                date: true,
                disabledDays: [],
                format: 'DD/MM/YYYY',
                shortTime: true,
                weekStart: 0,
                nowButton: false,
                cancelText: trans.getAttribute('data-date-picker-close'),
                clearText: trans.getAttribute('data-date-picker-clear'),
                nowText: trans.getAttribute('data-date-picker-now'),
                okText: trans.getAttribute('data-date-picker-validate'),
                switchOnClick: false,
                triggerEvent: 'focus',
                time: false,
                lang: lang,
                monthPicker: false,
                year: true
            }).on('change', function (event, date) {});
        }
    });
}