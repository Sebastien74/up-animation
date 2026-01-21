/**
 * To generate responsive tables
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (tables) {

    responsiveTables(tables);
    window.onresize = function () {
        responsiveTables(tables);
    }

    function responsiveTables(tables) {

        tables.forEach(function (table) {

            let inBody = table.closest('.header-table');
            let blockContent = table.closest('.layout-block-content');
            if (blockContent) {
                blockContent.classList.add('w-100');
            }

            if (inBody && window.innerWidth < 992) {

                let head = table.querySelector('tr:first-child');
                let cols = head.querySelectorAll('th');
                if (cols.length === 0) {
                    cols = head.querySelectorAll('td');
                }
                let colsCount = cols.length;
                let width = 100 / colsCount;

                let headElements = {};
                cols.forEach(function (col, i) {
                    if (typeof headElements['td' + i] === "object") {
                        headElements['td' + i].push(col.innerText);
                    } else if (headElements['td' + i]) {
                        headElements['td' + i] = [];
                        headElements['td' + i].push(col.innerText);
                    } else {
                        headElements['td' + i] = col.innerText;
                    }
                });

                console.log(headElements);

                let rows = table.querySelectorAll('tr');
                rows.forEach(function (row, i) {
                    if (i > 0) {
                        let cols = head.querySelectorAll('td');
                        if (cols.length === 0) {
                            cols = head.querySelectorAll('th');
                        }
                        cols.forEach(function (col, j) {
                            col.innerHTML = '<div class="content">' + col.innerHTML + '</div>';
                            col.setAttribute('data-title', headElements['td' + j]);
                            col.setAttribute('scope', 'col');
                            col.style.width = width + '%';
                            col.classList.add('d-inline-block');
                        });
                    }
                });

                table.classList.add('table-responsive', 'body-table');
            } else {

                table.classList.remove('table-responsive', 'body-table');
                let cols = table.querySelectorAll('td');
                cols.forEach(function (col) {
                    col.setAttribute('scope', 'col');
                    col.style.width = 'initial';
                    col.classList.remove('d-inline-block');
                });
            }
        });
    }
}