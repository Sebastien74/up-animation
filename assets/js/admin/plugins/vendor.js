import tagsInput from './bootstrap-tagsinput';
import sortable from './sortable';
import colorPicker from "./colorpicker";
import treeSelect from "./tree-select";
import {tinymcePlugin} from "./tinymce";
// import tooltips from "./tooltips";

/**
 * Plugins
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 *  1 - Tinymce
 *  2 - Nestable
 *  3 - Medias sortable
 *  4 - Prototypes sortable
 *  5 - CSV Table export
 *  6 - Sweet alert on delete
 *  7 - Sweet alert on click
 *  8 - Data table
 *  9 - Color picker
 *  10 - Tooltips
 *  11 - Tree Select
 *  12 - Sortable
 *  13 - Tag input
 *  14 - Mc datepicker
 */
export default function () {

    let body = document.body

    /** 1 - Tinymce */
    tinymcePlugin();

    /** 2 - Nestable */
    let nestableEls = body.querySelectorAll('.nestable-list-container');
    if (nestableEls.length > 0) {
        import('./nestable').then(({default: nestableModule}) => {
            new nestableModule();
        }).catch(error => console.error(error.message));
    }

    /** 3 - Medias sortable */
    let mediasSortableEls = body.querySelectorAll('#medias-sortable-container');
    if (mediasSortableEls.length > 0) {
        import('./medias-sortable').then(({default: mediasSortable}) => {
            new mediasSortable();
        }).catch(error => console.error(error.message));
    }

    /** 4 - Prototypes sortable */
    let prototypesSortableEls = body.querySelectorAll('.prototype-sortable');
    if (prototypesSortableEls.length > 0) {
        import('./prototype-sortable').then(({default: prototypesSortable}) => {
            new prototypesSortable();
        }).catch(error => console.error(error.message));
    }

    /** 5 - CSV Table export */
    document.body.addEventListener('click', function (e) {
        const csvExportBtn = e.target.closest('.csv-export');
        if (csvExportBtn) {
            import('./csv-table').then(({default: csvTable}) => {
                new csvTable(csvExportBtn);
            }).catch(error => console.error(error.message));
        }
    });

    /** 6 - Sweet alert on delete */
    document.body.addEventListener('click', function (e) {
        const swalDeleteBtn = e.target.closest('.swal-delete-link');
        if (swalDeleteBtn) {
            e.preventDefault();
            import('./sweet-alert').then(({default: sweetAlert}) => {
                new sweetAlert(e, swalDeleteBtn);
            }).catch(error => console.error(error.message));
        }
    });

    /** 7 - Sweet alert on click */
    document.body.addEventListener('click', function (e) {
        const swalLinkBtn = e.target.closest('.swal-link-confirm');
        if (swalLinkBtn) {
            e.preventDefault();
            import('./sweet-alert-link').then(({default: sweetAlert}) => {
                new sweetAlert(e, swalLinkBtn);
            }).catch(error => console.error(error.message));
        }
    });

    /** 8 - Data table */
    if (body.querySelectorAll('.data-table').length > 0) {
        import('./data-tables').then(({default: dataTables}) => {
            new dataTables();
        }).catch(error => console.error(error.message));
    }

    /** 9 - Color picker */
    colorPicker();

    // /** 10 - Tooltips */
    // tooltips();

    /** 11 - Tree Select */
    treeSelect();

    /** 12 - Sortable */
    sortable();

    /** 13 - Tag input */
    tagsInput();

    /** 14 - Mc datepicker */
    let mcDatepickerEls = document.querySelectorAll('input.mc-datepicker')
    if (mcDatepickerEls.length > 0) {
        import('./mc-datepicker').then(({default: flatDatepicker}) => {
            new flatDatepicker(mcDatepickerEls)
        }).catch(error => console.error(error.message));
    }
}