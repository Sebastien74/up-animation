import refresh from "./modules/refresh";
import charts from "./modules/charts";
import map from "./modules/map";

import '../../bootstrap/dist/tab';

import "../../plugins/vectormap/jquery-jvectormap-2.0.2.min";
import "../../plugins/vectormap/jquery-jvectormap-world-mill-en";
import "../../plugins/vectormap/jquery-jvectormap-in-mill";
import "../../plugins/vectormap/jquery-jvectormap-us-aea-en";
import "../../plugins/vectormap/jquery-jvectormap-uk-mill-en";
import "../../plugins/vectormap/jquery-jvectormap-au-mill";
import "../../plugins/vectormap/jquery-jvectormap-2.0.2.min";

import 'jquery-ui/dist/jquery-ui.min'
import '../../../vendor/plugins/i18n/jquery-ui-i18n.min';
import '../../../../scss/admin/widgets/vectormap/google-vector-map.scss';
import '../../../../scss/admin/widgets/vectormap/jquery-jvectormap-2.0.2.scss';
import '../../../../scss/admin/pages/analytics.scss';
// import '../../plugins/morris/morris.min';

$(function () {

    let body = $('body');

    charts();
    map();
    refresh(body);

    $('#main-sessions-report-card .nav-tabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
        charts();
    });

    // let locale = $('html').attr('lang');
    // let datepickerWrap = $('.js-datepicker-wrap');
    // let datepicker = $('.js-datepicker');
    // let weekStart = locale === 'en' ? 0 : 1;

    // datepicker.datepicker($.datepicker.regional[locale]);
    // $.datepicker.setDefaults({
    //     dateFormat: datepickerWrap.data('picker'),
    //     firstDay: weekStart,
    //     showAnim: "fadeIn",
    //     maxDate: new Date(),
    //     onSelect: function (date) {
    //         let picker = $(this);
    //         if (locale === 'en') {
    //             picker.val(moment(new Date(date)).format(datepickerWrap.data('moment')));
    //         } else {
    //             picker.val(moment(date, "DD/MM/YYYY").format(datepickerWrap.data('moment')));
    //         }
    //         picker.closest('form').submit();
    //     }
    // });

    // datepicker.datepicker({
    //     dateFormat: datepickerWrap.data('picker'),
    //     firstDay: weekStart,
    //     showAnim: "fadeIn",
    //     // viewMode: "years",
    //     // locale: locale,
    //     maxDate: new Date(),
    //     // showButtonPanel: true,
    //     // changeMonth: true,
    //     // changeYear: true,
    //     onSelect: function(date) {
    //         let picker = $(this);
    //         picker.closest('form').submit();
    //     }
    // });
});