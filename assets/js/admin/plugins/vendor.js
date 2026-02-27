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
    if (document.querySelector('.tinymce, .tinymce-simple')) {
        tinymcePlugin();
    }

    /** 2 - Nestable */
    if (document.querySelector('.nestable-list-container')) {
        import('./nestable').then(({default: nestableModule}) => {
            nestableModule();
        }).catch(error => console.error(error.message));
    }

    /** 3 - Medias sortable */
    if (document.querySelector('#medias-sortable-container')) {
        import('./medias-sortable').then(({default: mediasSortable}) => {
            mediasSortable();
        }).catch(error => console.error(error.message));
    }

    /** 4 - Prototypes sortable */
    if (document.querySelector('.prototype-sortable')) {
        import('./prototype-sortable').then(({default: prototypesSortable}) => {
            prototypesSortable();
        }).catch(error => console.error(error.message));
    }

    /** 5 - CSV Table export */
    document.body.addEventListener('click', function (e) {
        const csvExportBtn = e.target.closest('.csv-export');
        if (csvExportBtn) {
            import('./csv-table').then(({default: csvTable}) => {
                csvTable(csvExportBtn);
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
    if (document.querySelector('.data-table')) {
        import('./data-tables').then(({default: dataTables}) => {
            dataTables();
        }).catch(error => console.error(error.message));
    }

    /** 9 - Color picker */
    if (document.querySelector('.colorpicker')) {
        import('./colorpicker').then(({default: colorPicker}) => {
            colorPicker();
        }).catch(error => console.error(error.message));
    }

    // /** 10 - Tooltips */
    // tooltips();

    /** 11 - Tree Select */
    if (document.querySelector('.tree-select')) {
        import('./tree-select').then(({default: treeSelect}) => {
            treeSelect();
        }).catch(error => console.error(error.message));
    }

    /** 12 - Sortable */
    if (document.querySelector('.ui-sortable')) {
        import('./sortable').then(({default: sortable}) => {
            sortable();
        }).catch(error => console.error(error.message));
    }

    /** 13 - Tag input */
    if (document.querySelector('.bootstrap-tagsinput input, [data-role="tagsinput"]')) {
        import('./bootstrap-tagsinput').then(({default: tagsInput}) => {
            tagsInput();
        }).catch(error => console.error(error.message));
    }

    /** 14 - Mc datepicker */
    const mcDatepickerEls = document.querySelectorAll('input.mc-datepicker');
    if (mcDatepickerEls.length > 0) {
        import('./mc-datepicker').then(({default: flatDatepicker}) => {
            new flatDatepicker(mcDatepickerEls);
        }).catch(error => console.error(error.message));
    }
}