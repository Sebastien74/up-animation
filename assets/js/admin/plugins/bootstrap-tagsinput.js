/**
 * Bootstrap tags input
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    document.querySelectorAll('.bootstrap-tagsinput input').forEach(function (input) {
        input.addEventListener('keydown', function (event) {
            if (event.which === 13 || event.key === 'Enter') {
                input.blur();
                input.focus();
                event.preventDefault();
                return false;
            }
        });
    });
}
