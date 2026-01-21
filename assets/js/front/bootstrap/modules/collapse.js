/**
 * Collapse.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
export default function () {
    import('../dist/collapse').then(({default: Collapse}) => {

    }).catch(error => console.error(error.message));
}