/**
 * Form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (event) {
            let addon = event.target.closest('.custom-file .addon');
            if (addon) {
                let parent = addon.closest('.custom-file');
                let label = parent ? parent.querySelector('.custom-file-label') : null;
                if (label) {
                    label.click();
                }
            }
        });

        document.body.addEventListener('change', function (event) {
            let input = event.target.closest('.custom-file-input');
            if (input && input.files && input.files.length > 0) {
                let parent = input.closest('.custom-file');
                let label = parent ? parent.querySelector('.custom-file-label') : null;
                if (label) {
                    label.innerHTML = input.files[0].name;
                }
            }
        });
    });
};