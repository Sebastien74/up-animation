import '../../../scss/admin/pages/icons-library.scss';
import route from "../../vendor/components/routing";

const body = document.body;

/** To add Icon */
body.addEventListener('click', function (e) {

    const iconAdd = e.target.closest('.icon-add');
    if (!iconAdd) return;

    e.preventDefault();

    let el = iconAdd;
    let container = el.closest('.icon-wrap');
    let status = el.getAttribute('data-status') === 'true' ? 1 : 0;
    let newStatus = status ? 'false' : 'true';
    let routeName = status ? 'admin_icon_remove' : 'admin_icon_add';

    if (container) {
        container.classList.toggle('active');
    }

    fetch(route(routeName, {
        website: body.dataset.id,
        path: JSON.stringify(el.dataset.path)
    }), {
        method: "GET",
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(response => {

            if (newStatus === 'true') {
                el.setAttribute('data-original-title', el.dataset.removeTxt);
                const tooltipInner = el.parentElement.querySelector('.tooltip-inner');
                if (tooltipInner) {
                    tooltipInner.innerHTML = el.dataset.addTxt;
                }
            } else {
                el.setAttribute('data-original-title', el.dataset.addTxt);
                const tooltipInner = el.parentElement.querySelector('.tooltip-inner');
                if (tooltipInner) {
                    tooltipInner.innerHTML = el.dataset.removeTxt;
                }
            }

            el.setAttribute('data-status', newStatus);
            el.querySelectorAll('svg').forEach(svg => svg.classList.toggle('d-none'));

            if (newStatus === 'true' && !el.classList.contains('active')) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        })
        .catch(errors => {
            /** Display errors */
            import('../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
        });

    e.stopImmediatePropagation();
    return false;
});

/** To copy icon class */
body.addEventListener('click', function (e) {
    const iconCopy = e.target.closest('.icon-copy');
    if (!iconCopy) return;
    let img = iconCopy.closest('.icon-wrap').querySelector('img');
    let iconPath = img.getAttribute('src');
    copyText(iconPath);
});

/** To copy text */
function copyText(text) {
    let temp = document.createElement("input");
    document.body.appendChild(temp);
    temp.value = text;
    temp.select();
    document.execCommand("copy");
    temp.remove();
}

/** Icons search */
const searchInput = document.querySelector(".icons-search input");
if (searchInput) {
    searchInput.addEventListener('keyup', function () {

        let filter = this.value.toLowerCase();
        let iconsContents = document.getElementById("icons-contents");
        if (iconsContents) {
            iconsContents.querySelectorAll(".search-icon").forEach(function (el) {

                let icon = el.textContent.toLowerCase();
                let item = el.closest('.item');

                if (item) {
                    if (icon.indexOf(filter) !== -1) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                }
            });
        }
    });
}