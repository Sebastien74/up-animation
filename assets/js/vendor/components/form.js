/**
 * Form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    $(function () {
        $('.custom-file').on('click', '.addon', function (event) {
            $(this).parent().find('.custom-file-label').click();
        });
        $('.custom-file-input').on('change', function (event) {
            let inputFile = event.currentTarget;
            $(this).parent()
                .find('.custom-file-label')
                .html(inputFile.files[0].name);
        });
    });
};