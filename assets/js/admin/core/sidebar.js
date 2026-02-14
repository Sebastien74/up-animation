/**
 * Sidebar
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
document.querySelectorAll('.sidebar-nav').forEach(sidebar => {
    sidebar.querySelectorAll('.as-arrow').forEach(el => {
        el.addEventListener('click', () => {

            const parent = el.parentNode;
            const collapse = parent.querySelector('.collapse');
            const isActive = el.classList.contains('active');

            const isBigger = el.classList.contains('bigger');

            sidebar.querySelectorAll('.as-arrow').forEach(arrow => {
                const arrowIsBigger = arrow.classList.contains('bigger');
                if (isBigger === arrowIsBigger) {
                    arrow.classList.remove('active');
                }
            });

            sidebar.querySelectorAll('.collapse').forEach(item => {
                const itemParentArrow = item.parentNode.querySelector('.as-arrow');
                const itemIsBigger = itemParentArrow && itemParentArrow.classList.contains('bigger');
                if (isBigger === itemIsBigger) {
                    item.classList.remove('show');
                    item.classList.remove('in');
                }
            });

            if (!isActive) {
                el.classList.add('active');
                if (collapse && el.classList.contains('bigger')) {
                    collapse.classList.add('in');
                } else if (collapse) {
                    collapse.classList.add('show');
                }
            }
        });
    });
});

document.querySelectorAll('.open-sidebar').forEach(el => {
    el.onclick = function () {
        const sidebar = document.querySelector(el.dataset.target);
        const isOpen = sidebar.classList.contains('open');
        if (!isOpen) {
            document.querySelectorAll('.left-sidebar, .right-sidebar').forEach(item => {
                item.classList.remove('open');
            });
            document.querySelectorAll('.open-sidebar i').forEach(icon => {
                if (icon.classList.contains('icm-times')) {
                    icon.classList.add('d-none');
                } else {
                    icon.classList.remove('d-none');
                }
            });
        }
        sidebar.classList.toggle('open');
        el.querySelectorAll('i').forEach(icon => {
            icon.classList.toggle('d-none');
        });
    }
});