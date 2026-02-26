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

        let trans = document.getElementById('data-translation');

        return {
            processing: trans.getAttribute("data-processing"),
            search: trans.getAttribute("data-search"),
            lengthMenu: trans.getAttribute("data-datatable-display"),
            info: trans.getAttribute("data-datatable-info"),
            infoEmpty: trans.getAttribute("data-datatable-info-empty"),
            infoFiltered: trans.getAttribute('data-datatable-info-filtered'),
            infoPostFix: "",
            loadingRecords: trans.getAttribute("data-processing"),
            zeroRecords: trans.getAttribute("data-datatable-zero-records"),
            emptyTable: trans.getAttribute("data-datatable-empty-table"),
            paginate: {
                first: trans.getAttribute("data-first"),
                previous: trans.getAttribute("data-previous"),
                next: trans.getAttribute("data-next"),
                last: trans.getAttribute("data-last")
            },
            aria: {
                sortAscending: trans.getAttribute("data-datatable-sort-ascending"),
                sortDescending: trans.getAttribute("data-datatable-sort-descending")
            }
        };
    };

    let tables = document.querySelectorAll('body .data-table');

    tables.forEach(function (tableEl) {

        let pageLength = tableEl.getAttribute('data-length');
        let limit = pageLength !== null ? parseInt(pageLength) : 15;
        let pageHeight = tableEl.getAttribute('data-height');
        let height = pageHeight !== null ? pageHeight : null;
        let exportData = tableEl.getAttribute('data-export');
        let buttons = [];

        if (exportData !== null) {
            let exportDataExplode = exportData.split(',');
            for (let i = 0; i < exportDataExplode.length; i++) {
                if (exportDataExplode[i].trim() !== '') {
                    buttons.push(exportDataExplode[i].trim());
                }
            }
        }

        tableEl.classList.remove('data-table');
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
            jQuery(tableEl).DataTable({
                scrollY: height,
                pageLength: limit,
                dom: 'Bfrtip',
                buttons: buttons,
                language: dataTableIntl()
            });
        }
    });
}