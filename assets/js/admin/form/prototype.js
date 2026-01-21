import {tinymcePlugin} from '../plugins/tinymce';

/**
 * Prototype
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    $('body').on('click', '.add-collection', function (e) {

        e.preventDefault();

        let el = $(this);
        let collectionTarget = el.attr('data-collection-target');
        let beforeTarget = $(el.attr('data-before'));
        let collectionHolder = $(el.attr('data-target'));
        let newIndex = collectionHolder.attr('data-index');
        let prototype = collectionHolder.attr('data-prototype');
        let form = prototype.replace(/__name__/g, newIndex);

        collectionHolder.attr('data-index', parseInt(newIndex) + 1);

        // Form second level collection
        // if (form.match("data-prototype")) {
        //
        //     // Convert NODE list to array with (toArray) return by (htmlToElements) form to HTML elements
        //     let elements = toArray(htmlToElements(form));
        //
        //     $(elements).each(function (i, e) {
        //
        //         let prototypeExist = $(e).data('prototype');
        //
        //         // Check if prototype exist
        //         if (typeof prototypeExist != "undefined") {
        //             let secondIndex = 0;
        //             let secondPrototype = prototypeExist;
        //             form = secondPrototype.replace(/__second_name__/g, secondIndex);
        //         }
        //     });
        // }

        let body = $('body');
        let type = $(this).attr('data-type');
        if (typeof type != "undefined" && type === "table") {
            body.find('table .dataTables_empty').closest('tr').remove();
        }

        let formEl = $(form);
        let formCheckboxes = formEl.find('input[type="checkbox"]');

        if (beforeTarget.length > 0) {
            beforeTarget.before(form);
        } else if (typeof collectionTarget != "undefined") {
            if (collectionTarget === "prepend") {
                collectionHolder.prepend(form);
            } else if (collectionTarget === "append") {
                collectionHolder.append(form);
            }
        } else {
            collectionHolder.prepend(form);
        }

        let inputsPosition = collectionHolder.find('.input-position-collection').last();
        let inputPosition = inputsPosition.length > 0 ? inputsPosition.last() : null;
        if (inputPosition && !inputPosition.val()) {
            inputPosition.val(parseInt(newIndex) + 1)
        }

        if (formCheckboxes.length > 0) {
            let collections = $('body').find('.collection');
            collections.each(function () {
                let block = $(this).find('.prototype').last();
                let checkboxes = block.find('input[type="checkbox"]');
                checkboxes.each(function () {
                    let checkbox = $(this);
                    let uniqId = 'input-' + Math.floor(Math.random() * 10000);
                    checkbox.attr('id', uniqId);
                    checkbox.parent().find('label').attr('for', uniqId);
                });
            });
        }

        /** Plugins vendor */
        import('../../vendor/plugins/plugins').then(({default: activePlugins}) => {
            new activePlugins();
        }).catch(error => console.error(error.message));

        /** Plugins admin */
        import('../plugins/vendor').then(({default: activeAdminPlugins}) => {
            new activeAdminPlugins();
        }).catch(error => console.error(error.message));

        /** Touch spin */
        import('../../vendor/plugins/touchspin').then(({default: touchSpin}) => {
            new touchSpin();
        }).catch(error => console.error(error.message));

        import('./../form/btn-group-toggle').then(({default: btnToggle}) => {
            new btnToggle();
        }).catch(error => console.error(error.message));

        /** Code generator */
        if (body.find(".generate-code").length > 0) {
            import('../core/code-generator').then(({default: codeGenerator}) => {
                new codeGenerator();
            }).catch(error => console.error(error.message));
        }

        /** Tinymce */
        tinymcePlugin();

        return false;
    });
}