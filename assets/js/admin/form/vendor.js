/**
 * Forms
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 *  1 - Add ID to forms
 *  2 - Ajax Post
 *  3 - Prototype
 *  4 - Bootstrap Tags input
 *  5 - Color Picker
 *  6 - Assert
 *  7 - Dropzone
 *  8 - Dropify
 *  9 - Duplicate
 *  10 - Counter
 *  11 - Search in index
 *  12 - Entities status switcher
 *  13 - Loader on submitting
 *  14 - Input label btn
 *  15 - Date Picker
 *  16 - Btn toggle
 */

const body = document.body;

const showPasswordButtons = document.getElementsByClassName('show-password');
if (showPasswordButtons && showPasswordButtons.length > 0) {
    import("../../vendor/components/password-field").then(({default: passwords}) => {
        new passwords(showPasswordButtons)
    }).catch(error => console.error(error.message));
}

/** 1 - Add ID to forms */
const forms = document.querySelectorAll('form');
if (forms.length > 0) {
    forms.forEach(function (form) {
        const id = form.getAttribute('id');
        if (!id) {
            const uniqId = 'form-' + Math.floor(Math.random() * 10000);
            form.setAttribute('id', uniqId);
        }
    });
}

/** 2 - Ajax Post */
if (document.querySelector('.ajax-post')) {
    import('./ajax').then(({default: ajax}) => {
        ajax();
    }).catch(error => console.error(error.message));
}

/** 3 - Prototype */
if (document.querySelector('.add-collection')) {
    import('./prototype').then(({default: prototype}) => {
        new prototype();
    }).catch(error => console.error(error.message));
}

/** 4 - Bootstrap Tags input */
if (document.querySelector('[data-role="tagsinput"]')) {
    import('./../lib/bootstrap-tagsinput.min').then(({default: tagsInputModule}) => {
        new tagsInputModule();
    }).catch(error => console.error(error.message));
}

/** 5 - Color Picker */
if (document.querySelector('.colorpicker')) {
    import('./../plugins/colorpicker').then(({default: asColorPicker}) => {
        new asColorPicker();
    }).catch(error => console.error(error.message));
}

/** 6 - Assert */
if (document.querySelector('.modal')) {
    import('./assert').then(({default: assertModal}) => {
        new assertModal();
    }).catch(error => console.error(error.message));
}

/** 7 - Dropzone */
if (document.querySelector('.js-reference-dropzone')) {
    import('./dropzone').then(({default: dropzone}) => {
        new dropzone();
    }).catch(error => console.error(error.message));
}

/** 8 - Dropify */
if (document.querySelector('.dropify')) {
    import('./dropify').then(({default: dropify}) => {
        new dropify();
    }).catch(error => console.error(error.message));
}

/** 9 - Duplicate */
if (document.querySelector('.duplicate-btn')) {
    import('./duplicate').then(({default: duplicate}) => {
        new duplicate();
    }).catch(error => console.error(error.message));
}

/** 10 - Counter */
if (document.querySelector('.counter-form-group')) {
    import('./counter').then(({default: counter}) => {
        new counter();
    }).catch(error => console.error(error.message));
}

/** 11 - Search in index */
// const searchIndexEl = document.querySelector('#index-search-submit');
// if (searchIndexEl) {
//     import('./search-index').then(({default: searchIndex}) => {
//         new searchIndex();
//     }).catch(error => console.error(error.message));
// }

/** 12 - Entities status switcher */
if (document.querySelector('.entity-switcher-status')) {
    import('./entity-switcher').then(({default: switcher}) => {
        switcher();
    }).catch(error => console.error(error.message));
}

/** 13 - Loader on submitting */
document.body.addEventListener('click', function (e) {
    const btn = e.target.closest("button[type='submit']");
    if (!btn) return;
    if (!btn.classList.contains('ajax-post') && !btn.classList.contains('disable-preloader')) {
        const referPreloader = btn.closest('.refer-preloader');
        const stripePreloader = referPreloader ? referPreloader.querySelector('.stripe-preloader') : null;
        const loader = stripePreloader ? stripePreloader : document.body.querySelector('.main-preloader');
        if (loader) loader.classList.remove('d-none');
    }
});

/** 14 - Input label btn */
document.body.addEventListener('change', function (e) {
    const inputBtn = e.target.closest('.input-btn');
    if (!inputBtn) return;
    document.querySelectorAll('.input-btn').forEach(el => {
        const label = el.closest('label');
        if (label) label.classList.remove('active');
    });
    const label = inputBtn.closest('label');
    if (label) label.classList.add('active');
});

/** 15 - Date Picker */
if (document.querySelector('.datepicker')) {
    import('./date-pickers').then(({default: datepickerPlugin}) => {
        new datepickerPlugin();
    }).catch(error => console.error(error.message));
}

/** 16 - Btn toggle */
if (document.querySelector('.btn-group-toggle')) {
    import('./btn-group-toggle').then(({default: btnToggle}) => {
        new btnToggle();
    }).catch(error => console.error(error.message));
}