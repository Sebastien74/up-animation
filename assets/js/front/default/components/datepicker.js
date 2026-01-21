import '../trash/bootstrap/modules/material-datetimepicker';

/**
 * Date pickers
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (pickers) {

    let trans = $('#data-translation');
    let locale = $('html').attr('lang');
    let weekStart = locale === 'en' ? 0 : 1;
    let shortTime = locale === 'en';

    pickers.each(function () {

        let datepicker = $(this);
        let type = datepicker.data('type');
        let hasTime = type === 'hour';
        let format = trans.data('format-date');

        if (type === 'hour') {
            format = 'HH:mm';
        }

        datepicker.bootstrapMaterialDatePicker({
            minDate: null, /** new Date(datepicker.val()) */
            maxDate: null,
            currentDate: null,
            date: !hasTime,
            disabledDays: [],
            format: format,
            shortTime: shortTime,
            weekStart: weekStart,
            nowButton: false,
            cancelText: trans.data('close'),
            clearText: trans.data('clear'),
            nowText: trans.data('now'),
            okText: trans.data('validate'),
            switchOnClick: false,
            triggerEvent: 'focus',
            time: hasTime,
            lang: locale,
            monthPicker: false,
            year: true
        }).change(function (event, date) {
        });
    });
}