import select2 from "../../vendor/plugins/select2";
import ajaxRowProcess from "./ajax-row";

/**
 * Search in index
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let body = document.querySelector('body');

    body.addEventListener('keydown', function (e) {
        if (e.target.matches('input#index_search_search') && e.which === 13) {
            e.preventDefault();
            let submitBtn = document.getElementById('index-search-submit');
            if (submitBtn) {
                submitBtn.click();
            }
        }
    });

    body.addEventListener('click', function (e) {

        let submitBtn = e.target.closest('#index-search-submit');
        if (!submitBtn) {
            return;
        }

        let loader = document.getElementById('index-preloader');
        if (loader) {
            loader.classList.toggle('d-none');
        }

        let input = submitBtn.closest('.form-group').querySelector('input');
        let value = input.value;
        let form = input.closest('form');
        let formId = form.getAttribute('id');
        let uri = location.pathname.substr(1) + "?index_search[search]=" + value;
        let formData = new FormData(document.getElementById(formId));

        let url = '/' + uri + "&ajax=true";
        history.replaceState("", "", '/' + uri);

        fetch(url, {
            method: 'GET',
            // Note: FormData as GET body is not standard, usually you'd append to URL.
            // But the original code was doing: data: formData, type: "GET".
            // jQuery actually converts formData to query string for GET.
            // For simplicity and to match jQuery behavior for GET:
        })
            .then(response => response.json())
            .then(response => {

                let tempDiv = document.createElement('div');
                tempDiv.innerHTML = response.html;
                let html = tempDiv.querySelector("#result");
                let ajaxContent = document.getElementById("result");
                if (ajaxContent && html) {
                    ajaxContent.replaceWith(html);
                }

                let body = document.body;
                let preloader = body.querySelector('#index-preloader');
                if (preloader) {
                    preloader.classList.remove('d-none');
                }

                let showBtnDelete = body.querySelector('#index-delete-show');
                if (showBtnDelete && showBtnDelete.classList.contains('d-none')) {
                    showBtnDelete.classList.remove('d-none');
                }

                let removeBtn = body.querySelector('#index-delete-submit');
                if (removeBtn && !removeBtn.classList.contains('d-none')) {
                    removeBtn.classList.add('d-none');
                }

                let pagination = body.querySelectorAll('#entities-index .pagination .page-link');
                pagination.forEach(function (link) {
                    let href = link.getAttribute('href');
                    if (href) {
                        let newHref = href.replace('&ajax=true', '');
                        link.setAttribute('href', newHref);
                    }
                });

                if (preloader) {
                    preloader.classList.add('d-none');
                }

                select2();
                ajaxRowProcess();
            })
            .catch(error => {
                console.error('Error:', error);
            });

        e.stopImmediatePropagation();
        return false;
    });
}