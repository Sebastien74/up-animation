import '../../../../../../node_modules/vanillajs-datepicker/dist/css/datepicker.min.css';
import '../../../../../../node_modules/vanillajs-datepicker/dist/css/datepicker-bs4.min.css';
import fr from '../../../../../../node_modules/vanillajs-datepicker/js/i18n/locales/fr';
import es from '../../../../../../node_modules/vanillajs-datepicker/js/i18n/locales/es';
import it from '../../../../../../node_modules/vanillajs-datepicker/js/i18n/locales/it';
import de from '../../../../../../node_modules/vanillajs-datepicker/js/i18n/locales/de';

import {Datepicker} from 'vanillajs-datepicker'

// VOIR POUR REMPLACER PAR
// https://mcdatepicker.netlify.app/docs/theme

/**
 * Date pickers
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @doc https://mymth.github.io/vanillajs-datepicker/#/?id=quick-start
 */
export default function (pickers) {

    Object.assign(Datepicker.locales, fr, es, it, de);

    let trans = document.getElementById('data-translation')
    let locale = document.documentElement.getAttribute('lang')
    let weekStart = locale === 'en' ? 0 : 1
    let shortTime = locale === 'en'

    for (let i = 0; i < pickers.length; i++) {

        let datepicker = pickers[i]
        let type = datepicker.dataset.type
        let hasTime = type === 'hour'
        let displayTime = hasTime || type === 'datetime'
        let format = trans.dataset.formatDatepicker

        if (type === 'hour') {
            format = 'HH:mm'
        } else if (type === 'datetime') {
            format = format + ' HH:mm'
        }

        const datepickerJS = new Datepicker(datepicker, {
            language: locale,
            format: format,
            weekStart: weekStart,
            daysShort: shortTime,
            autohide: true,
            clearBtn: true
        })

        datepicker.addEventListener('changeDate', (event) => {
            let changeEvent = new Event('change')
            datepicker.dispatchEvent(changeEvent)
            changeEvent.stopPropagation()
        })
    }
}