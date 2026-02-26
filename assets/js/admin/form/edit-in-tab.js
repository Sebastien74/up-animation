/**
 * Entity in tab
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import 'jquery-ui/dist/jquery-ui.min'
import '../bootstrap/dist/tab';
import setPositions from "./edit-in-tab-positions";

import '../../../scss/admin/pages/edit-in-tab.scss';
import '../../../scss/admin/lib/sweetalert.scss';
import '../lib/sweetalert/sweetalert.min';
import {tinymcePlugin} from "../plugins/tinymce";

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

    let featuresSortableEl = document.getElementById('features-sortable');
    if (featuresSortableEl && typeof jQuery !== 'undefined' && typeof jQuery.fn.sortable !== 'undefined') {
        let featuresSortable = jQuery(featuresSortableEl).sortable({
            placeholder: "ui-state-highlight",
            items: '.ui-feature',
            handle: ".handle-feature",
            start: function (e, ui) {
                ui.placeholder.height(ui.item.height());
            },
            update: function (event, ui) {

                const loader = document.querySelector('.main-preloader');
                const loaderContent = document.querySelector('#entity-preloader');
                const sortables = Array.from(featuresSortableEl.querySelectorAll('.ui-feature'));
                const length = sortables.length;
                const progressBarCard = loaderContent ? loaderContent.querySelector('.progress-card') : null;
                const progressBar = progressBarCard ? progressBarCard.querySelector('.position-progress-bar') : null;

                if (loader) loader.classList.remove('d-none');
                if (loaderContent) loaderContent.classList.remove('d-none');
                if (progressBarCard) progressBarCard.classList.remove('d-none');

                sortables.forEach((el, i) => {
                    const newPosition = i + 1;
                    const path = el.getAttribute('data-pos-path');
                    const url = path + (path.indexOf('?') > -1 ? '&' : '?') + 'position=' + newPosition;

                    fetch(url, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.ok ? r.json().catch(() => ({})) : Promise.reject(r))
                        .then(() => {
                            if (progressBar) {
                                const progress = Math.ceil((i * 100) / length);
                                progressBar.style.width = progress + '%';
                                progressBar.setAttribute('aria-valuenow', progress + '%');
                                progressBar.innerHTML = progress + '%';
                            }
                            if ((i + 1) === length) {
                                if (loader) loader.classList.add('d-none');
                                if (loaderContent) loaderContent.classList.add('d-none');
                                if (progressBarCard) progressBarCard.classList.add('d-none');
                                if (progressBar) {
                                    progressBar.style.width = '0%';
                                    progressBar.setAttribute('aria-valuenow', '0%');
                                    progressBar.innerHTML = '0%';
                                }
                            }
                        })
                        .catch(errors => {
                            import('../core/errors').then(({default: displayErrors}) => {
                                new displayErrors(errors);
                            }).catch(error => console.error(error.message));
                        });
                });
                event.stopImmediatePropagation();
            }
        });

        featuresSortable.disableSelection();
    }

    let featureValuesSortableEls = document.querySelectorAll('#features-sortable .feature-values-sortable');
    if (featureValuesSortableEls.length > 0 && typeof jQuery !== 'undefined' && typeof jQuery.fn.sortable !== 'undefined') {
        let featureValuesSortable = jQuery(featureValuesSortableEls).sortable({
            placeholder: "ui-state-highlight",
            items: '.ui-value',
            handle: ".handle-value",
            start: function (e, ui) {
                ui.placeholder.height(ui.item.height());
            },
            update: function (event, ui) {
                const items = document.querySelectorAll('#features-sortable .ui-value');
                setPositions(items);
                event.stopImmediatePropagation();
            }
        });

        featureValuesSortable.disableSelection();
    }

    let videoValuesSortableEl = document.getElementById('videos-sortable');
    if (videoValuesSortableEl && typeof jQuery !== 'undefined' && typeof jQuery.fn.sortable !== 'undefined') {
        let videoValuesSortable = jQuery(videoValuesSortableEl).sortable({
            placeholder: "ui-state-highlight",
            items: '.ui-video',
            handle: ".handle-video",
            start: function (e, ui) {
                ui.placeholder.height(ui.item.height());
            },
            update: function (event, ui) {
                const items = videoValuesSortableEl.querySelectorAll('.ui-video');
                setPositions(items);
                event.stopImmediatePropagation();
            }
        });

        videoValuesSortable.disableSelection();
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