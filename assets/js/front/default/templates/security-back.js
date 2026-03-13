/** Import JS */

const form = document.querySelector('form');
if (form) {
    import('../components/form/form').then(({default: Form}) => {
        new Form();
    }).catch(error => console.error(error.message));
}