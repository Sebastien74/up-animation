/**
 * Dropdowns
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    import('../dist/dropdown').then(({default: Dropdown}) => {

        document.querySelectorAll('.dropdown-toggle').forEach((dropdownToggleEl) => {

            dropdownToggleEl.classList.add('loaded');
            if (!dropdownToggleEl.classList.contains('loaded')) {
                new Dropdown(dropdownToggleEl);
            }

            dropdownToggleEl.addEventListener('show.bs.dropdown', () => {
                const parentEl = dropdownToggleEl.closest('.dropdown');
                const menu = parentEl ? parentEl.querySelector('.dropdown-menu') : false;
                if (parentEl) parentEl.classList.add('active');
                if (menu) menu.classList.add('active');
            });

            dropdownToggleEl.addEventListener('hide.bs.dropdown', () => {
                const parentEl = dropdownToggleEl.closest('.dropdown');
                const menu = parentEl ? parentEl.querySelector('.dropdown-menu') : false;
                if (parentEl) parentEl.classList.remove('active');
                if (menu) menu.classList.remove('active');
            });
        });
    }).catch((error) => console.error(error.message));
}