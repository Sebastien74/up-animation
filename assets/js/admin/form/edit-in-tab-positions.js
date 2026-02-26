/**
 * Set positions
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (items) {

    const loader = document.body.querySelector('#entity-preloader');
    if (loader) loader.classList.remove('d-none');

    const dataToSend = [];

    // Normalize items: accept jQuery collection, NodeList, or Array
    let list = [];
    if (items) {
        if (typeof items.length !== 'undefined' && !items.nodeType) {
            // Likely a collection
            list = Array.from(items instanceof NodeList || Array.isArray(items) ? items : items);
        } else if (items.nodeType === 1) {
            list = [items];
        }
    }

    const firstItem = list[0];

    if (firstItem) {

        const pathAjax = firstItem.dataset ? firstItem.dataset.posPath : (items[0] && items[0].getAttribute ? items[0].getAttribute('data-pos-path') : null);

        list.forEach((el, i) => {
            const newPosition = i + 1;
            const elementId = el.dataset ? el.dataset.id : el.getAttribute('data-id');
            const inputPosition = el.querySelector('.input-position');
            if (inputPosition) {
                inputPosition.value = newPosition;
            }
            const target = document.getElementById(elementId);
            if (target) {
                target.setAttribute('data-position', String(newPosition));
            }
            dataToSend.push({ id: elementId, position: newPosition });
        });

        if (pathAjax) {
            const body = 'data=' + encodeURIComponent(JSON.stringify(dataToSend));
            fetch(pathAjax, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body
            })
                .then(response => {
                    if (!response.ok) throw response;
                    return response.json().catch(() => ({}));
                })
                .then(() => {
                    if (loader) loader.classList.add('d-none');
                })
                .catch(errors => {
                    import('../core/errors').then(({default: displayErrors}) => {
                        new displayErrors(errors);
                    }).catch(error => console.error(error.message));
                });
        }
    }
}