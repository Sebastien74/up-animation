/**
 * Copy
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (e) {
            let el = e.target.closest('.copy-link');
            if (el) {
                let refer = el.closest('.refer-copy');
                let toCopy = refer ? refer.querySelector('.to-copy') : null;
                let text = toCopy ? toCopy.textContent : '';
                copyText(text, refer);
            }
        });

        let copyText = function (text, refer) {
            if (!refer) return;
            let temp = document.createElement("input");
            refer.appendChild(temp);
            temp.value = text;
            temp.select();
            document.execCommand("copy");
            temp.remove();
        }
    });
};