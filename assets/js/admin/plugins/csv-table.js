/**
 * CSV Table
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (el) {

    function downloadCsv(csv, filename) {

        let csvFile;
        let downloadLink;

        /** CSV FILE */
        csvFile = new Blob([csv], {type: "text/csv"});
        /** Download link */
        downloadLink = document.createElement("a");
        /** File name */
        downloadLink.download = filename;
        /** We have to create a link to the file */
        downloadLink.href = window.URL.createObjectURL(csvFile);
        /** Make sure that the link is not displayed */
        downloadLink.style.display = "none";
        /** Add the link to your DOM */
        document.body.appendChild(downloadLink);

        downloadLink.click();
    }

    function exportTableToCsv(html, filename) {

        let csv = [];
        let rows = document.querySelectorAll("table tr");

        for (let i = 0; i < rows.length; i++) {

            let row = [], cols = rows[i].querySelectorAll("td, th");
            for (let j = 0; j < cols.length; j++) {
                row.push(cols[j].innerText);
            }

            csv.push(row.join(","));
        }

        /** Download CSV */
        downloadCsv(csv.join("\n"), filename);
    }

    let tableId = el.data('table');
    let html = $('#' + tableId).outerHTML;
    exportTableToCsv(html, "table.csv");
}
