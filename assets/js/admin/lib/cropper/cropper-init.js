import '../../bootstrap/dist/modal';

/**
 * Cropper
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
document.addEventListener('DOMContentLoaded', function () {

    /** Init modals */
    document.querySelectorAll('.crop-modal').forEach(function (modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            initCropper(modalEl);
        });
    });

    document.body.addEventListener('click', function (e) {
        let refreshBtn = e.target.closest('.refresh-cropper-sizes');
        if (refreshBtn) {
            let modal = refreshBtn.closest('.modal');
            let wrap = modal.querySelector('.cropper-wrap');
            wrap.dataset.width = modal.querySelector('input.dataWidth').value;
            wrap.dataset.height = modal.querySelector('input.dataHeight').value;
            initCropper(modal, true);
        }
    });

    /** Init cropper */
    function initCropper(modal, refresh = false) {
        let idModal = modal.getAttribute('id');
        let wrap = modal.querySelector('.cropper-wrap');
        let imageEl = wrap.querySelector('.image');
        let dataWidth = wrap.dataset.width;
        let dataHeight = wrap.dataset.height;
        let preview = wrap.querySelector('.img-preview');
        let previewSelector = preview ? preview.getAttribute('class') : null;

        if (refresh && typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
            jQuery(imageEl).cropper('destroy');
            imageEl = wrap.querySelector('.image');
        }

        if (parseInt(dataWidth) === 0) {
            dataWidth = '';
        }
        if (parseInt(dataHeight) === 0) {
            dataHeight = '';
        }

        let fieldX = wrap.querySelector('.dataX');
        let fieldY = wrap.querySelector('.dataY');
        let fieldWidth = wrap.querySelector('.dataWidth');
        let fieldHeight = wrap.querySelector('.dataHeight');
        let fieldRotate = wrap.querySelector('.dataRotate');
        let fieldScaleX = wrap.querySelector('.dataScaleX');
        let fieldScaleY = wrap.querySelector('.dataScaleY');
        let txtWidth = wrap.querySelector('.txtWidth');
        let txtHeight = wrap.querySelector('.txtHeight');

        //VAR FOR CORNER CALC
        let tempImageHeight = 0;
        let tempImageWidth = 0;
        let tempContainerDataHeight = 0;
        let tempContainerDataWidth = 0;
        let tempOffsetX = 0;
        let tempOffsetY = 0;

        let options = {
            viewMode: 1,
            responsive: true,
            zoomOnWheel: true,
            crop: function (e) {
                // Cropper.js v3 fires a jQuery $.Event with detail merged as
                // direct event properties (not under .detail like native CustomEvent).
                let detail = (e && e.detail) || e || {};
                let modalINJS = document.getElementById(idModal);
                let canvasINJS = modalINJS ? modalINJS.querySelector('.cropper-canvas') : null;
                if (canvasINJS) {
                    tempContainerDataHeight = canvasINJS.offsetHeight;
                    tempContainerDataWidth = canvasINJS.offsetWidth;
                }
                if (fieldX && typeof detail.x === 'number') fieldX.value = Math.round(detail.x);
                if (fieldY && typeof detail.y === 'number') fieldY.value = Math.round(detail.y);
                if (fieldWidth && typeof detail.width === 'number') fieldWidth.value = Math.round(detail.width);
                if (fieldHeight && typeof detail.height === 'number') fieldHeight.value = Math.round(detail.height);
                if (fieldRotate && typeof detail.rotate !== 'undefined') fieldRotate.value = detail.rotate;
                if (fieldScaleX && typeof detail.scaleX !== 'undefined') fieldScaleX.value = detail.scaleX;
                if (fieldScaleY && typeof detail.scaleY !== 'undefined') fieldScaleY.value = detail.scaleY;
                if (txtWidth && typeof detail.width === 'number') txtWidth.textContent = Math.round(detail.width);
                if (txtHeight && typeof detail.height === 'number') txtHeight.textContent = Math.round(detail.height);

                if (typeof detail.height === 'number') tempImageHeight = Math.round(detail.height);
                if (typeof detail.width === 'number') tempImageWidth = Math.round(detail.width);
                if (typeof detail.x === 'number') tempOffsetX = Math.round(detail.x);
                if (typeof detail.y === 'number') tempOffsetY = Math.round(detail.y);
            },
            cropend: function (e) {
                //CALC CORNER
                let inLeftCorner = tempOffsetX < 2;
                let inTopCorner = tempOffsetY < 2;
                let inBottomCorner = tempOffsetY + tempImageHeight >= (tempContainerDataHeight - 2);
                let inRightCorner = tempOffsetX + tempImageWidth >= (tempContainerDataWidth - 2);
                //REWRITE IF COLLAPSED BORDER
                if ((inLeftCorner || inTopCorner || inBottomCorner || inRightCorner) && typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
                    let xPosition = tempOffsetX;
                    let yPosition = tempOffsetY;
                    let widthImage = tempImageWidth;
                    let heightImage = tempImageHeight;
                    if (inLeftCorner) {
                        xPosition = xPosition + 1;
                    }
                    if (inTopCorner) {
                        yPosition = yPosition + 1;
                    }
                    if (inBottomCorner) {
                        if (inTopCorner) {
                            heightImage = heightImage - 2;
                            widthImage = widthImage - 2;
                        } else {
                            heightImage = heightImage - 1;
                            widthImage = widthImage - 1;
                        }
                    }
                    if (inRightCorner) {
                        if (inLeftCorner) {
                            heightImage = heightImage - 2;
                            widthImage = widthImage - 2;
                        } else {
                            heightImage = heightImage - 1;
                            widthImage = widthImage - 1;
                        }
                    }
                    jQuery(imageEl).cropper("setData", {
                        "x": xPosition,
                        "y": yPosition,
                        "width": widthImage,
                        "height": heightImage
                    });
                }
            }
        };

        if (previewSelector) {
            options.preview = previewSelector;
        }

        if (dataWidth !== "" && dataHeight !== "") {
            let ratio = dataWidth / dataHeight;
            options['aspectRatio'] = ratio;
        } else if (dataWidth === "" && dataHeight !== "") {
            options['aspectRatio'] = 16 / 9;
        } else if (dataWidth !== "" && dataHeight === "") {
            options['aspectRatio'] = 9 / 16;
        }

        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
            jQuery(imageEl).cropper(options);
        }

        wrap.querySelectorAll('.move-img').forEach(btn => {
            btn.addEventListener('click', function () {
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
                    jQuery(imageEl).cropper("setDragMode", btn.classList.contains('move-img') ? "move" : "crop");
                }
            });
        });

        wrap.querySelectorAll('.zoom-in').forEach(btn => {
            btn.addEventListener('click', function () {
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
                    jQuery(imageEl).cropper("zoom", 0.1);
                }
            });
        });

        wrap.querySelectorAll('.zoom-out').forEach(btn => {
            btn.addEventListener('click', function () {
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
                    jQuery(imageEl).cropper("zoom", -0.1);
                }
            });
        });

        wrap.querySelectorAll('.move-start, .move-left').forEach(btn => {
            btn.addEventListener('click', function () {
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
                    jQuery(imageEl).cropper("move", -10, 0);
                }
            });
        });

        wrap.querySelectorAll('.move-end, .move-right').forEach(btn => {
            btn.addEventListener('click', function () {
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
                    jQuery(imageEl).cropper("move", 10, 0);
                }
            });
        });

        wrap.querySelectorAll('.move-up').forEach(btn => {
            btn.addEventListener('click', function () {
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
                    jQuery(imageEl).cropper("move", 0, -10);
                }
            });
        });

        wrap.querySelectorAll('.move-down').forEach(btn => {
            btn.addEventListener('click', function () {
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
                    jQuery(imageEl).cropper("move", 0, 10);
                }
            });
        });

        wrap.querySelectorAll('.rotate-start, .rotate-left').forEach(btn => {
            btn.addEventListener('click', function () {
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
                    jQuery(imageEl).cropper("rotate", -90);
                }
            });
        });

        wrap.querySelectorAll('.rotate-end, .rotate-right').forEach(btn => {
            btn.addEventListener('click', function () {
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
                    jQuery(imageEl).cropper("rotate", 90);
                }
            });
        });

        wrap.querySelectorAll('.flip-horizontal').forEach(btn => {
            btn.addEventListener('click', function () {
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
                    let scale = parseFloat(btn.dataset.scale || "1");
                    let resetScale = scale === -1 ? 1 : -1;
                    btn.dataset.scale = String(resetScale);
                    jQuery(imageEl).cropper("scaleX", scale);
                }
            });
        });

        wrap.querySelectorAll('.flip-vertical').forEach(btn => {
            btn.addEventListener('click', function () {
                if (typeof jQuery !== 'undefined' && typeof jQuery.fn.cropper !== 'undefined') {
                    let scale = parseFloat(btn.dataset.scale || "1");
                    let resetScale = scale === -1 ? 1 : -1;
                    btn.dataset.scale = String(resetScale);
                    jQuery(imageEl).cropper("scaleY", scale);
                }
            });
        });
    }
});