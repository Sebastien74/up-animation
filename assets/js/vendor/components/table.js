/**
 * To generate responsive tables
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

let tables = $('body').find('table');

responsiveTables(tables);

$(window).resize(function () {
    responsiveTables(tables);
});

function responsiveTables(tables) {

    tables.each(function () {

        let table = $(this);
        let inBody = table.closest('.body.header-table');

        if (inBody.length > 0 && $(window).width() < 992) {

            let head = table.find('tr:first-child');
            let cols = head.find('td');
            let colsCount = cols.length;
            let width = 100 / colsCount;

            let headElements = {};
            for (let i = 0; i < cols.length; i++) {
                if (typeof headElements['td' + i] === "object") {
                    headElements['td' + i].push($(cols[i]).text());
                } else if (headElements['td' + i]) {
                    headElements['td' + i] = [];
                    headElements['td' + i].push($(cols[i]).text());
                } else {
                    headElements['td' + i] = $(cols[i]).text();
                }
            }

            table.find('tr').each(function (i) {
                if (i > 0) {
                    $(this).find('td').each(function (j) {

                        let col = $(this);
                        let html = '<div class="content">' + col.html() + '</div>';

                        col.html(html);
                        col.attr('data-title', headElements['td' + j]);
                    });
                }
            });

            table.addClass('table-responsive body-table');
            table.find('td').attr('scope', 'col').css('width', width + '%').addClass('d-inline-block');
        } else {

            table.closest('.table-responsive').removeClass('table-responsive');
            table.removeClass('table-responsive');
            table.removeClass('body-table');
            table.find('td').attr('scope', 'col').css('width', 'initial').removeClass('d-inline-block');
        }
    });
}