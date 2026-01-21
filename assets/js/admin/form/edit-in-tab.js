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

$(function () {

    let body = $('body');
    let trans = body.find('#entity-translations');
    let preloader = $("#entity-preloader");
    let form = $('#form-entity');

    body.on('click', '#save-entity', function () {
        preloader.removeClass('d-none');
        form.submit();
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

    body.on('click', '#medias-path', function (e) {
        e.preventDefault();
        let path = $(this).attr('href');
        if ($('#entity-edition').hasClass('is-entity')) {
            return swal({
                title: trans.data('swal-entity-title'),
                text: trans.data('swal-entity-text'),
                type: "info",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: trans.data('swal-media-confirm-text'),
                cancelButtonText: trans.data('swal-entity-cancel-text'),
                closeOnConfirm: false
            }, function () {
                document.location.href = path;
                preloader.removeClass('d-none');
            });
        }
    });

    body.on('click', '.swal-entity-value', function (e) {

        e.preventDefault();

        let path = $(this).attr('href');

        return swal({
            title: trans.data('swal-entity-title'),
            text: trans.data('swal-entity-text'),
            type: "info",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: trans.data('swal-value-confirm-text'),
            cancelButtonText: trans.data('swal-entity-cancel-text'),
            closeOnConfirm: false
        }, function () {
            document.location.href = path;
            preloader.removeClass('d-none');
        });
    });

    let featuresSortable = $('#features-sortable').sortable({
        placeholder: "ui-state-highlight",
        items: '.ui-feature',
        handle: ".handle-feature",
        start: function (e, ui) {
            ui.placeholder.height(ui.item.height());
        },
        update: function (event, ui) {

            let loader = body.find('.main-preloader');
            let loaderContent = body.find('#entity-preloader');
            let sortables = $('body').find('.ui-feature');
            let length = sortables.length;
            let progressBarCard = loaderContent.find(".progress-card")
            let progressBar = progressBarCard.find(".position-progress-bar")

            loader.removeClass('d-none');
            loaderContent.removeClass('d-none');
            progressBarCard.removeClass('d-none');

            sortables.each(function (i, el) {

                let newPosition = i + 1;
                let path = $(el).data('pos-path');

                $.ajax({
                    url: path + "?position=" + newPosition,
                    type: "GET",
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    async: false,
                    beforeSend: function () {
                    },
                    success: function (response) {

                        let progress = Math.ceil((i * 100) / length)
                        progressBar.attr('style', 'width:' + progress + '%');
                        progressBar.attr('aria-valuenow', progress + '%');
                        progressBar.html(progress + '%');

                        if ((i + 1) === length) {
                            loader.addClass('d-none');
                            loaderContent.addClass('d-none');
                            progressBarCard.addClass('d-none');
                            progressBar.attr('style', '0%');
                            progressBar.attr('aria-valuenow', '0%');
                            progressBar.html('0%');
                        }
                    },
                    error: function (errors) {
                        /** Display errors */
                        import('../core/errors').then(({default: displayErrors}) => {
                            new displayErrors(errors);
                        }).catch(error => console.error(error.message));
                    }
                });
            });
            event.stopImmediatePropagation();
        }
    });

    featuresSortable.disableSelection();

    let featureValuesSortable = $('.feature-values-sortable').sortable({
        placeholder: "ui-state-highlight",
        items: '.ui-value',
        handle: ".handle-value",
        start: function (e, ui) {
            ui.placeholder.height(ui.item.height());
        },
        update: function (event, ui) {
            let items = $('body').find('#features-sortable .ui-value');
            setPositions(items);
            event.stopImmediatePropagation();
        }
    });

    featureValuesSortable.disableSelection();

    let videoValuesSortable = $('#videos-sortable').sortable({
        placeholder: "ui-state-highlight",
        items: '.ui-video',
        handle: ".handle-video",
        start: function (e, ui) {
            ui.placeholder.height(ui.item.height());
        },
        update: function (event, ui) {
            let items = $('body').find('#videos-sortable .ui-video');
            setPositions(items);
            event.stopImmediatePropagation();
        }
    });

    videoValuesSortable.disableSelection();
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