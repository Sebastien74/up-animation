import '../../../../scss/front/default/components/_calendar.scss';

import route from "../../../vendor/components/routing";
import allLocales from '@fullcalendar/core/locales-all';
import {Calendar} from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';

/**
 * Agenda
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let entitiesData = document.getElementById('entities-data')
    let agenda = entitiesData.dataset.agenda
    let calendarEl = document.getElementById('calendar-render')
    let locale = document.documentElement.getAttribute('lang')
    let card = document.getElementById('agenda-info-card')
    let preloader = document.getElementById('agenda-card-preloader')

    let dayNames = {1: 'S', 2: 'M', 3: 'T', 4: 'W', 5: 'T', 6: 'F', 0: 'S'};
    if (locale === 'fr') {
        dayNames = {1: 'L', 2: 'M', 3: 'M', 4: 'J', 5: 'V', 6: 'S', 0: 'D'};
    }

    let events = [];
    if (entitiesData) {
        let eventsEls = entitiesData.getElementsByClassName('event')
        for (let i = 0; i < eventsEls.length; i++) {
            let event = eventsEls[i]
            events.push({
                'start': event.dataset.start,
                'end': event.dataset.end
            })
        }
    }

    let ajaxModal = function (url) {

        if (preloader) {
            preloader.classList.remove('d-none')
        }

        let xHttp = new XMLHttpRequest()
        xHttp.open("GET", url, true)
        xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8")
        xHttp.send()
        xHttp.onload = function (e) {
            if (this.readyState === 4 && this.status === 200) {

                let response = JSON.parse(this.response)
                let htmlEl = document.createElement('div')
                htmlEl.innerHTML = response.html
                let ajaxContent = card.getElementsByClassName("ajax-content")[0]

                ajaxContent.innerHTML = htmlEl.getElementsByClassName("ajax-content")[0].innerHTML

                if (preloader) {
                    preloader.classList.add('d-none')
                }
            }
        }
    }

    let betweenToDates = function (startDate, stopDate) {

        let dateArray = [];
        let start = moment(startDate)
        let end = moment(stopDate)

        if (moment(startDate).format('YYYY-MM-DD') === moment(stopDate).format('YYYY-MM-DD')) {
            return [new Date(startDate.getTime() - (startDate.getTimezoneOffset() * 60000))
                .toISOString()
                .split("T")[0]]
        }

        while (start <= end) {
            dateArray.push(moment(start).format('YYYY-MM-DD'))
            start = moment(start).add(1, 'days')
        }

        dateArray.push(moment(end).format('YYYY-MM-DD'))

        return dateArray
    }

    let calendar = new Calendar(calendarEl, {
        plugins: [
            dayGridPlugin,
            interactionPlugin,
            listPlugin,
        ],
        headerToolbar: {
            left: 'prev',
            center: 'title',
            right: 'next'
        },
        showNonCurrentDates: true,
        locales: allLocales,
        events: events,
        editable: false,
        locale: locale,
        contentHeight: window.innerWidth > 576 ? 400 : 310,
        dayHeaderContent: function (day) {
            return dayNames[day.dow]
        },
        dayCellClassNames: function (day) {
        },
        eventsSet: function (events) {

            for (let i = 0; i < events.length; i++) {

                let event = events[i];
                let range = event._instance.range;
                let start = range.start;
                let end = moment(range.end).subtract(1, 'day').toDate();
                let dates = betweenToDates(start, end);

                for (let j = 0; j < dates.length; j++) {
                    let elByData = document.querySelector("td[data-date='" + dates[j] + "']")
                    if (elByData) {
                        elByData.classList.add('fc-have-event')
                    }
                }
            }
        },
        eventClick: function (event) {
        },
        dateClick: function (info) {

            let dayEl = info.dayEl

            let activeDays = document.getElementsByClassName('active-day')
            for (let i = 0; i < activeDays.length; i++) {
                activeDays[i].classList.remove('active-day')
            }
            dayEl.classList.add('active-day')

            if (!dayEl.classList.contains('fc-day-past')) {

                let day = info.dateStr
                let eventData = document.querySelector(".event[data-day='" + day + "']")
                let period = eventData ? eventData.getAttribute('id') : null

                ajaxModal(route('front_agenda_period', {
                    "agenda": agenda,
                    "date": day
                }) + '?period=' + period)
            }
        }
    })

    calendar.render()
}