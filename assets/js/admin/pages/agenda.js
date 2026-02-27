require('../../../scss/admin/pages/agenda.scss');

import '../bootstrap/dist/modal';

import resetModal from "../../vendor/components/reset-modal";
import route from "../../vendor/components/routing";
import allLocales from '@fullcalendar/core/locales-all';
import {Calendar} from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

document.querySelectorAll('.alert').forEach(alert => alert.remove());

document.addEventListener('DOMContentLoaded', function () {

    let entitiesData = document.getElementById('entities-data');
    let eventsDaysData = document.getElementById('events-days-data');
    let agenda = entitiesData ? entitiesData.dataset.agenda : null;
    let calendarEl = document.getElementById('calendar');

    let events = [];
    if (entitiesData) {
        entitiesData.querySelectorAll('.event').forEach(function (event) {
            events.push({
                'start': event.dataset.start,
                'end': event.dataset.end
            });
        });
    }

    let ajaxModal = function (action) {

        fetch(action + "?ajax=true", {
            method: "GET",
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(response => {

                if (response.html) {

                    document.querySelectorAll('.modal-event-agenda').forEach(modal => modal.remove());

                    document.body.insertAdjacentHTML('beforeend', response.html);
                    let modalEl = document.body.lastElementChild;
                    if (!modalEl.classList.contains('modal')) {
                        modalEl = modalEl.querySelector('.modal');
                    }
                    
                    let modal = new bootstrap.Modal(modalEl);
                    modal.show();

                    /** Refresh select2 */
                    import('../../vendor/plugins/select2').then(({default: select2}) => {
                        select2();
                    }).catch(error => console.error(error.message));

                    modalEl.addEventListener("hidden.bs.modal", function () {
                        resetModal(modalEl, true);
                        modalEl.remove();
                    });
                }
            })
            .catch(errors => {
                /** Display errors */
                import('../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            });
    };

    let calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, interactionPlugin],
        locales: allLocales,
        events: events,
        editable: false,
        locale: document.documentElement.lang,
        contentHeight: 500,
        dateClick: function (info) {

            let day = info.dateStr;
            let dayEl = info.dayEl;
            let eventData = eventsDaysData ? eventsDaysData.querySelector(".event[data-day='" + day + "']") : null;

            if (eventData && !dayEl.classList.contains('fc-day-past')) {
                ajaxModal(route('admin_agendaperiod_edit_item', {
                    "website": document.body.dataset.id,
                    "agenda": agenda,
                    "period": eventData.dataset.id
                }));
            } else if (!dayEl.classList.contains('fc-day-past')) {
                ajaxModal(route('admin_agendaperiod_new', {
                    "website": document.body.dataset.id,
                    "agenda": agenda,
                    "date": info.dateStr
                }));
            } else {
                alert('Date dépassée !!');
            }
        },
        eventClick: function (info) {

            let event = info.el;
            let dayEl = event.closest('.fc-daygrid-day');
            let day = dayEl.dataset.date;
            let eventData = eventsDaysData ? eventsDaysData.querySelector(".event[data-day='" + day + "']") : null;

            if (eventData) {
                ajaxModal(route('admin_agendaperiod_edit_item', {
                    "website": document.body.dataset.id,
                    "agenda": agenda,
                    "period": eventData.dataset.id
                }));
            }
        }
    });

    calendar.render();
});