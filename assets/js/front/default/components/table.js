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

            let blockContent = table.closest('.layout-block-content');
            if (blockContent) {
                blockContent.classList.add('w-100');
            }

            if (window.innerWidth < 992) {

                let body = table.querySelector('tbody');
                let head = table.querySelector('thead tr');

                if (!head) {
                    head = table.querySelector('tr:first-child');
                } else {
                    table.classList.add('have-head');
                }
                if (!head) return;

                if (!head && !body) {
                    table.classList.add('no-head-body');
                } else if (!head && body) {
                    table.classList.add('no-head');
                }

                let colsHead = head.querySelectorAll('th');
                if (colsHead.length === 0) {
                    colsHead = head.querySelectorAll('td');
                }

                let headElements = {};
                colsHead.forEach(function (col, i) {
                    headElements['td' + i] = col.innerHTML.trim();
                });

                let rows = table.querySelectorAll('tr');
                rows.forEach(function (row, i) {
                    if (i > 0) {
                        let cells = row.querySelectorAll('td');
                        cells.forEach(function (cell, j) {
                            if (!cell.querySelector('.table-content')) {
                                cell.innerHTML = '<div class="table-content mt-2">' + cell.innerHTML + '</div>';
                            }
                            if (headElements['td' + j] && !cell.querySelector('.table-title')) {
                                let titleElement = document.createElement('div');
                                titleElement.classList.add('table-title');
                                titleElement.innerHTML = headElements['td' + j];
                                cell.insertBefore(titleElement, cell.firstChild);
                            }
                            cell.setAttribute('scope', 'col');
                        });
                    }
                });
                table.classList.add('body-table');
            } else {
                table.classList.remove('body-table');
                let cells = table.querySelectorAll('td');
                cells.forEach(function (cell) {
                    let content = cell.querySelector('.table-content');
                    let title = cell.querySelector('.table-title');
                    if (title) {
                        title.remove();
                    }
                    if (content) {
                        cell.innerHTML = content.innerHTML;
                    }
                });
            }
        });
    }
}