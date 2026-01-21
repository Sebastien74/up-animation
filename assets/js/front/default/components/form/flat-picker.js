import 'flatpickr'
import 'flatpickr/dist/l10n/fr'
import 'flatpickr/dist/l10n/es'
import 'flatpickr/dist/l10n/it'
import 'flatpickr/dist/l10n/de'
import 'flatpickr/dist/l10n/nl'
import "../../../../../scss/front/default/components/form/_flatpickr.scss"

export default function (pickers) {

    let trans = document.getElementById('data-translation')
    let localeSplit = document.documentElement.getAttribute('lang').split('_')
    let locale = localeSplit.shift()

    pickers.forEach(picker => {

        let enableTime = true;
        /** Add same variable in form type (formatInput) */
        let formatBase = trans.dataset.formatInput;
        let altFormat = formatBase + " H:i:s";
        let noCalendar = false;
        let type = picker.dataset.type

        if (type === 'date') {
            enableTime = false;
            altFormat = formatBase;
        }

        if (type === 'hours') {
            noCalendar = true;
            enableTime = true;
            altFormat = "H:i";
        }

        picker.flatpickr({
            mode: "single",
            altInput: true,
            altFormat: altFormat,
            noCalendar: noCalendar,
            dateFormat: altFormat,
            enableTime: enableTime,
            // minDate: "today",
            maxDate: "today",
            position: "auto left",
            "locale": locale,
        });

        picker.removeAttribute('readonly');
    });
}