/**
 * Entity switcher form
 */
export default function () {

    document.body.addEventListener('change', function (e) {
        const target = e.target.closest('.entity-switcher-status');
        if (!target) return;

        e.preventDefault();

        const form = target.closest('form');
        const input = form ? form.querySelector('input') : null;
        const status = input ? input.checked : false;
        const loader = document.getElementById('index-preloader');

        let url = form ? form.getAttribute('action') : '';
        if (url.indexOf('?') > -1) {
            url = url + "&status=" + status;
        } else {
            url = url + "?status=" + status;
        }

        if (loader) loader.classList.remove('d-none');

        fetch(url, {
            method: form ? (form.getAttribute('method') || 'GET').toUpperCase() : 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(response => {
                if (response.reload) {
                    setTimeout(function () {
                        location.reload();
                    }, 200);
                } else {
                    if (loader) loader.classList.add('d-none');
                }
            })
            .catch(errors => {
                import('../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            });

        e.stopImmediatePropagation();
        return false;
    });
}