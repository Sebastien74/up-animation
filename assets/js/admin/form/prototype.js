import {tinymcePlugin} from '../plugins/tinymce';

/**
 * Prototype
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    document.body.addEventListener('click', function (e) {
        const trigger = e.target.closest('.add-collection');
        if (!trigger) return;

        e.preventDefault();

        const collectionTarget = trigger.getAttribute('data-collection-target');
        const beforeSelector = trigger.getAttribute('data-before');
        const targetSelector = trigger.getAttribute('data-target');
        const collectionHolder = targetSelector ? document.querySelector(targetSelector) : null;
        const beforeTarget = beforeSelector ? document.querySelector(beforeSelector) : null;
        if (!collectionHolder) return false;

        const newIndex = collectionHolder.getAttribute('data-index');
        const prototype = collectionHolder.getAttribute('data-prototype');
        let form = prototype ? prototype.replace(/__name__/g, newIndex) : '';

        collectionHolder.setAttribute('data-index', String(parseInt(newIndex || '0') + 1));

        const type = trigger.getAttribute('data-type');
        if (typeof type !== 'undefined' && type === 'table') {
            const empty = document.querySelector('table .dataTables_empty');
            const tr = empty ? empty.closest('tr') : null;
            if (tr) tr.remove();
        }

        // Insert HTML
        if (beforeTarget) {
            beforeTarget.insertAdjacentHTML('beforebegin', form);
        } else if (typeof collectionTarget !== 'undefined' && collectionTarget) {
            if (collectionTarget === 'prepend') {
                collectionHolder.insertAdjacentHTML('afterbegin', form);
            } else if (collectionTarget === 'append') {
                collectionHolder.insertAdjacentHTML('beforeend', form);
            }
        } else {
            collectionHolder.insertAdjacentHTML('afterbegin', form);
        }

        // Work on the newly inserted node
        const temp = document.createElement('div');
        temp.innerHTML = form;
        const inserted = temp.firstElementChild;

        // Update position if needed
        const inputsPosition = collectionHolder.querySelectorAll('.input-position-collection');
        const inputPosition = inputsPosition.length > 0 ? inputsPosition[inputsPosition.length - 1] : null;
        if (inputPosition && !inputPosition.value) {
            inputPosition.value = String(parseInt(newIndex || '0') + 1);
        }

        // Ensure unique IDs for any checkboxes inside collections
        if (inserted && inserted.querySelector('input[type="checkbox"]')) {
            document.querySelectorAll('.collection').forEach(function (collection) {
                const block = collection.querySelectorAll('.prototype');
                const lastBlock = block.length > 0 ? block[block.length - 1] : collection;
                lastBlock.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
                    const uniqId = 'input-' + Math.floor(Math.random() * 10000);
                    checkbox.setAttribute('id', uniqId);
                    const parent = checkbox.parentElement;
                    if (parent) {
                        const label = parent.querySelector('label');
                        if (label) label.setAttribute('for', uniqId);
                    }
                });
            });
        }

        /** Plugins vendor */
        import('../../vendor/plugins/plugins').then(({default: activePlugins}) => {
            activePlugins();
        }).catch(error => console.error(error.message));

        /** Plugins admin */
        import('../plugins/vendor').then(({default: activeAdminPlugins}) => {
            activeAdminPlugins();
        }).catch(error => console.error(error.message));

        import('./../form/btn-group-toggle').then(({default: btnToggle}) => {
            btnToggle();
        }).catch(error => console.error(error.message));

        /** Code generator */
        if (document.querySelector('.generate-code')) {
            import('../core/code-generator').then(({default: codeGenerator}) => {
                codeGenerator();
            }).catch(error => console.error(error.message));
        }

        /** Tinymce */
        tinymcePlugin();

        return false;
    });
}