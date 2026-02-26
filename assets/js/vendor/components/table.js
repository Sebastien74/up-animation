/**
 * To generate responsive tables
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

let tables = document.querySelectorAll('body table');

responsiveTables(tables);

window.addEventListener('resize', function () {
    responsiveTables(tables);
});

function responsiveTables(tables) {

    tables.forEach(function (table) {

        let inBody = table.closest('.body.header-table');

        if (inBody && window.innerWidth < 992) {

            let head = table.querySelector('tr:first-child');
            let cols = head ? head.querySelectorAll('td') : [];
            let colsCount = cols.length;
            let width = 100 / colsCount;

            let headElements = {};
            cols.forEach(function (col, i) {
                let text = col.textContent.trim();
                if (Array.isArray(headElements['td' + i])) {
                    headElements['td' + i].push(text);
                } else if (headElements['td' + i]) {
                    let oldVal = headElements['td' + i];
                    headElements['td' + i] = [oldVal, text];
                } else {
                    headElements['td' + i] = text;
                }
            });

            table.querySelectorAll('tr').forEach(function (row, i) {
                if (i > 0) {
                    row.querySelectorAll('td').forEach(function (col, j) {
                        if (!col.querySelector('.content')) {
                            let html = '<div class="content">' + col.innerHTML + '</div>';
                            col.innerHTML = html;
                        }
                        col.setAttribute('data-title', headElements['td' + j]);
                    });
                }
            });

            table.classList.add('table-responsive', 'body-table');
            table.querySelectorAll('td').forEach(function (col) {
                col.setAttribute('scope', 'col');
                col.style.width = width + '%';
                col.classList.add('d-inline-block');
            });
        } else {
            let responsiveParent = table.closest('.table-responsive');
            if (responsiveParent) {
                responsiveParent.classList.remove('table-responsive');
            }
            table.classList.remove('table-responsive', 'body-table');
            table.querySelectorAll('td').forEach(function (col) {
                col.setAttribute('scope', 'col');
                col.style.width = 'initial';
                col.classList.remove('d-inline-block');
            });
        }
    });
}