import '../../../scss/admin/lib/dataTables.bootstrap5.scss';
import 'datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css';
// import 'datatables.net-buttons/js/buttons.flash.min';

const jsZip = require('jszip');
const pdfMake = require('pdfmake/build/pdfmake.js');
const pdfFonts = require('pdfmake/build/vfs_fonts.js');

pdfMake.vfs = pdfFonts.pdfMake.vfs;
window.JSZip = jsZip;

import '../lib/dataTables.bootstrap5.js';
import 'datatables.net-buttons/js/buttons.html5.min';
import 'datatables.net-buttons/js/buttons.print.min';

/**
 * DataTable
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    /**
     * DataTable internationalization
     */
    let dataTableIntl = function () {

        let trans = $('#data-translation');

        return {
            processing: trans.data("processing"),
            search: trans.data("search"),
            lengthMenu: trans.data("datatable-display"),
            info: trans.data("datatable-info"),
            infoEmpty: trans.data("datatable-info-empty"),
            infoFiltered: trans.data('datatable-info-filtered'),
            infoPostFix: "",
            loadingRecords: trans.data("processing"),
            zeroRecords: trans.data("datatable-zero-records"),
            emptyTable: trans.data("datatable-empty-table"),
            paginate: {
                first: trans.data("first"),
                previous: trans.data("previous"),
                next: trans.data("next"),
                last: trans.data("last")
            },
            aria: {
                sortAscending: trans.data("datatable-sort-ascending"),
                sortDescending: trans.data("datatable-sort-descending")
            }
        };
    };

    let tables = $('body').find('.data-table');

    tables.each(function () {

        let table = $(this);
        let pageLength = table.data('length');
        let limit = typeof pageLength != 'undefined' ? pageLength : 15;
        let pageHeight = table.data('height');
        let height = typeof pageHeight != 'undefined' ? pageHeight : null;
        let exportData = table.data('export');
        let buttons = [];

        if (typeof exportData != "undefined") {
            let exportDataExplode = exportData.split(',');
            for (let i = 0; i < exportDataExplode.length; i++) {
                if (exportDataExplode[i].trim() !== '') {
                    buttons.push(exportDataExplode[i].trim());
                }
            }
        }

        table.removeClass('data-table');
        table.DataTable({
            scrollY: height,
            pageLength: limit,
            dom: 'Bfrtip',
            buttons: buttons,
            language: dataTableIntl()
        });
    });
}