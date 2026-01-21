import '../../bootstrap/dist/modal';
import '../../bootstrap/dist/tooltip';

import resetModal from "../../../vendor/components/reset-modal";
import select2 from "../../../vendor/plugins/select2";
import touchspin from "../../../vendor/plugins/touchspin";
import dropify from "../../form/dropify";

/**
 * On edit element btn click
 */
export default function (Routing) {

    /**
     * On input change in new element form
     */
    let showSubmit = function (modal) {
        $('body .edit-element-form').on('change', 'input', function () {
            let form = modal.find('form');
            let btn = form.find('.modal-buttons button');
            if (btn.hasClass('d-none')) {
                btn.removeClass('d-none');
            }
        });
    };

    /**
     * On submit
     */
    let submit = function () {
        $('body').on('click', '.edit-element-submit-btn', function (e) {
            e.preventDefault();
            let body = $('body');
            let modal = body.find('.layout-modal');
            if (!body.hasClass('ajax-posted')) {
                let form = $(this).closest('.edit-element-form');
                /** Refresh layout */
                import('./refresh-layout').then(({default: refreshLayout}) => {
                    new refreshLayout(Routing, form, modal, e);
                }).catch(error => console.error(error.message));
            }
        });
    };

    /**
     * On add Block
     */
    let addBlock = function () {
        $('body').on('click', '.btn-add-block', function () {
            $('body').find('#main-preloader').toggleClass('d-none');
        });
    };

    /**
     * Background modal
     */
    let backgroundModal = function (modal) {
        let modalId = modal.attr('id');
        $('#' + modalId + ' .background-rounded-selector').on('change', 'input', function () {
            let el = $(this);
            let elId = el.attr('id');
            let body = $('body');
            body.find('.background-input-label-active').removeClass('active');
            body.find('input#' + elId).closest('.background-input-label-active').addClass('active');
        });
    };

    /**
     * Background modal
     */
    let copyClass = function (modal) {

        modal.on("shown.bs.modal", function () {
            let modal = $(this);
            $('body').on('click', '.class-copy', function () {
                let el = $(this);
                let text = el.parent().find('.text-copy').text();
                let field = modal.find('.input-css');
                let copy = field.val() === "" ? text : field.val() + " " + text;
                field.val(copy);
            });
        });
    };

    /**
     * Tabs height
     */
    let tabHeight = function (modal) {

        modal.on("shown.bs.modal", function () {

            let modal = $(this);
            let maxHeight = 0;
            let tabs = modal.find('.config-tabs-content .tab-pane-config');

            tabs.each(function() {
                let tab = $(this);
                tab.addClass("active");
                maxHeight = (tab.height() > maxHeight ? tab.height() : maxHeight);
                if (!tab.hasClass("show")) {
                    tab.removeClass("active");
                }
            });

            tabs.each(function() {
                $(this).height(maxHeight);
            });
        });
    };

    /**
     * Input label btn
     */
    let inputLabelBtn = function () {
        let body = $('body');
        body.on('change', '.input-btn', function () {
            let el = $(this);
            let elId = el.attr('id');
            body.find('.input-btn').closest('label').removeClass('active');
            body.find('input#' + elId).closest('label').addClass('active');
        });
    };

    /**
     * Show modal
     */
    $('body').on('click', '.edit-layout-element-btn', function handler(e) {

        e.preventDefault();

        let btn = $(this);
        let body = $('body');
        let loader = body.find('#layout-preloader');

        $.ajax({
            url: btn.data('path') + "?ajax=true",
            type: "GET",
            processData: false,
            contentType: false,
            dataType: 'json',
            async: true,
            beforeSend: function () {
                loader.toggleClass('d-none');
            },
            success: function (response) {

                let html = response.html;
                let body = $('body');
                let container = body.find('#layout-grid')

                container.append(html);

                $('[data-bs-toggle="tooltip"]').tooltip();

                let modal = body.find('.layout-modal');
                let modalEl = body.find('#' + modal.attr('id'));

                modalEl.modal('show');
                loader.addClass('d-none');

                select2();
                dropify();
                touchspin();

                $("#layout-preloader").addClass('d-none');

                $('[data-bs-toggle="preloader"]').on('click', function () {
                    $("#main-preloader").toggleClass('d-none');
                    let el = $(this);
                    let stripePreloader = el.closest('.refer-preloader').find('.stripe-preloader');
                    let preloader = stripePreloader.length > 0 ? stripePreloader : body.find("#layout-preloader");
                    preloader.removeClass('d-none');
                });

                inputLabelBtn();
                showSubmit(modalEl);
                submit();
                backgroundModal(modalEl);
                copyClass(modalEl);
                tabHeight(modalEl);
                addBlock();

                import('../../form/btn-group-toggle').then(({default: btnToggle}) => {
                    new btnToggle();
                }).catch(error => console.error(error.message));

                let colorPicker = body.find('.colorpicker');
                if (colorPicker.length > 0) {
                    import('./../../plugins/colorpicker').then(({default: asColorPicker}) => {
                        new asColorPicker();
                    }).catch(error => console.error(error.message));
                }

                $(modalEl).on('click', '.reset-margins', function (e) {
                    e.preventDefault();
                    import('./../../plugins/sweet-alert').then(({default: sweetAlert}) => {
                        new sweetAlert(e, $(this));
                    }).catch(error => console.error(error.message));
                });

                modalEl.on("hide.bs.modal", function () {
                    resetModal(modalEl, true);
                    $('.modal-wrapper').remove();
                });
            },
            error: function (errors) {

                let body = $('body');
                let modal = body.find('.modal');

                /** Display errors */
                import('../../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));

                resetModal(modal, true);
            }
        });

        e.stopImmediatePropagation();
        return false;
    });
}