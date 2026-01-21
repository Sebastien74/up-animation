require('../../../scss/admin/pages/agenda.scss');

import '../bootstrap/dist/modal';

import resetModal from "../../vendor/components/reset-modal";
import route from "../../vendor/components/routing";
import allLocales from '@fullcalendar/core/locales-all';
import {Calendar} from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

$('.alert').remove();

document.addEventListener('DOMContentLoaded', function () {

    let entitiesData = $('#entities-data');
    let eventsDaysData = $('#events-days-data');
    let agenda = entitiesData.data('agenda');
    let calendarEl = document.getElementById('calendar');

    let events = [];
    if (entitiesData.length > 0) {
        entitiesData.find('.event').each(function () {
            let event = $(this);
            events.push({
                'start': event.data('start'),
                'end': event.data('end')
            });
        });
    }

    let ajaxModal = function (action) {

        $.ajax({
            url: action + "?ajax=true",
            type: "GET",
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
                $('.modal-event-agenda').remove();
            },
            success: function (response) {

                if (response.html) {

                    let modalEl = $(response.html);

                    $('body').append(modalEl);
                    modalEl.modal('show');

                    /** Refresh select2 */
                    import('../../vendor/plugins/select2').then(({default: select2}) => {
                        new select2();
                    }).catch(error => console.error(error.message));

                    modalEl.on("hide.bs.modal", function () {
                        resetModal(modalEl, true);
                        $('.modal-event-agenda').remove();
                    });
                }
            },
            error: function (errors) {
                /** Display errors */
                import('../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            }
        });
    };

    let calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, interactionPlugin],
        locales: allLocales,
        events: events,
        editable: false,
        locale: $('html').attr('lang'),
        contentHeight: 500,
        dateClick: function (info) {

            let day = info.dateStr;
            let dayEl = info.dayEl;
            let eventData = eventsDaysData.find(".event[data-day='" + day + "']");

            if (eventData.length > 0 && !$(dayEl).hasClass('fc-day-past')) {
                ajaxModal(route('admin_agendaperiod_edit_item', {
                    "website": $('body').data('id'),
                    "agenda": agenda,
                    "period": eventData.data('id')
                }));
            } else if (!$(dayEl).hasClass('fc-day-past')) {
                ajaxModal(route('admin_agendaperiod_new', {
                    "website": $('body').data('id'),
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
            let day = $(dayEl).data('date');
            let eventData = eventsDaysData.find(".event[data-day='" + day + "']");

            ajaxModal(route('admin_agendaperiod_edit_item', {
                "website": $('body').data('id'),
                "agenda": agenda,
                "period": eventData.data('id')
            }));
        }
    });

    calendar.render();
});