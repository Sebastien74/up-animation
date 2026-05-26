/**
 * Entity in tab
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import '../bootstrap/dist/tab';
import setPositions from "./edit-in-tab-positions";
import flatSortable from "../plugins/flat-sortable";
import '../../../scss/admin/pages/edit-in-tab.scss';
import '../../../scss/admin/lib/sweetalert.scss';
import '../lib/sweetalert/sweetalert.min';

document.addEventListener('DOMContentLoaded', function () {

    const body = document.body;
    const trans = body.querySelector('#entity-translations');
    const preloader = document.querySelector('#entity-preloader');
    const form = document.querySelector('#form-entity');

    body.addEventListener('click', function (e) {
        const saveBtn = e.target.closest('#save-entity');
        if (!saveBtn) return;
        if (preloader) preloader.classList.remove('d-none');
        if (form) form.submit();
    });

    const saveBack = document.querySelector('#save-back-entity');
    if (saveBack) {
        saveBack.onclick = function (e) {
            e.preventDefault();
            tinymcePlugin();
            preloader.removeClass('d-none');
            const form = document.querySelector('#form-entity');
            if (form) {
                let xHttp = new XMLHttpRequest();
                xHttp.open("POST", form.getAttribute('action') + '?ajax=true', true);
                xHttp.send(new FormData(form));
                xHttp.onload = function () {
                    if (this.readyState === 4 && this.status === 200) {
                        let response = this.response;
                        response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
                        response = JSON.parse(response);
                        if (response.success) {
                            window.location.href = saveBack.dataset.path;
                        }
                    }
                }
            }
        }
    }

    body.addEventListener('click', function (e) {
        const mediasPath = e.target.closest('#medias-path');
        if (!mediasPath) return;
        e.preventDefault();
        const path = mediasPath.getAttribute('href');
        const entityEdition = document.getElementById('entity-edition');
        if (entityEdition && entityEdition.classList.contains('is-entity')) {
            return swal({
                title: trans?.dataset.swalEntityTitle,
                text: trans?.dataset.swalEntityText,
                type: "info",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: trans?.dataset.swalMediaConfirmText,
                cancelButtonText: trans?.dataset.swalEntityCancelText,
                closeOnConfirm: false
            }, function () {
                document.location.href = path;
                if (preloader) preloader.classList.remove('d-none');
            });
        }
    });

    body.addEventListener('click', function (e) {
        const valueBtn = e.target.closest('.swal-entity-value');
        if (!valueBtn) return;
        e.preventDefault();
        const path = valueBtn.getAttribute('href');
        return swal({
            title: trans?.dataset.swalEntityTitle,
            text: trans?.dataset.swalEntityText,
            type: "info",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: trans?.dataset.swalValueConfirmText,
            cancelButtonText: trans?.dataset.swalEntityCancelText,
            closeOnConfirm: false
        }, function () {
            document.location.href = path;
            if (preloader) preloader.classList.remove('d-none');
        });
    });

    const featuresSortableEl = document.getElementById('features-sortable');
    if (featuresSortableEl) {
        flatSortable(featuresSortableEl, {
            handle: ".handle-feature",
            draggable: ".ui-feature",
            onUpdate: function () {

                const loader = document.querySelector('.main-preloader');
                const loaderContent = document.querySelector('#entity-preloader');
                const sortables = Array.from(featuresSortableEl.querySelectorAll('.ui-feature'));
                const length = sortables.length;

                if (loader) loader.classList.remove('d-none');
                if (loaderContent) loaderContent.classList.remove('d-none');

                sortables.forEach((el, i) => {
                    const newPosition = i + 1;
                    const path = el.getAttribute('data-pos-path');
                    const url = path + (path.indexOf('?') > -1 ? '&' : '?') + 'position=' + newPosition;

                    fetch(url, {method: 'GET', headers: {'X-Requested-With': 'XMLHttpRequest'}})
                        .then(r => r.ok ? r.json().catch(() => ({})) : Promise.reject(r))
                        .then(() => {
                            if ((i + 1) === length) {
                                if (loader) loader.classList.add('d-none');
                                if (loaderContent) loaderContent.classList.add('d-none');
                            }
                        })
                        .catch(errors => {
                            import('../core/errors').then(({default: displayErrors}) => {
                                new displayErrors(errors);
                            }).catch(error => console.error(error.message));
                        });
                });
            }
        });
    }

    const featureValuesSortableEls = document.querySelectorAll('#features-sortable .feature-values-sortable');
    if (featureValuesSortableEls.length > 0) {
        featureValuesSortableEls.forEach(el => {
            flatSortable(el, {
                handle: ".handle-value",
                draggable: ".ui-value",
                onUpdate: function () {
                    const items = document.querySelectorAll('#features-sortable .ui-value');
                    setPositions(items);
                }
            });
        });
    }

    const videoValuesSortableEl = document.getElementById('videos-sortable');
    if (videoValuesSortableEl) {
        flatSortable(videoValuesSortableEl, {
            handle: ".handle-video",
            draggable: ".ui-video",
            onUpdate: function () {
                const items = videoValuesSortableEl.querySelectorAll('.ui-video');
                setPositions(items);
            }
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".handle-value").forEach((handle) => {
        handle.addEventListener("mousedown", function () {
            handle.classList.add("dragging");
        })
        document.addEventListener("mouseup", function () {
            handle.classList.remove("dragging");
        })
    })
})