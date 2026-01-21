import '../../bootstrap/dist/modal';

/**
 * Cropper
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
$(function () {
    /** Init modals */
    $('.crop-modal').each(function () {
        let modal = $(this);
        modal.on('shown.bs.modal', function () {
            initCropper(modal);
        });
    });
    $('body').on('click', '.refresh-cropper-sizes', function () {
        let modal = $(this).closest('.modal');
        let wrap = modal.find('.cropper-wrap');
        wrap.data('width', modal.find('input.dataWidth').val());
        wrap.data('height', modal.find('input.dataHeight').val());
        initCropper(modal, true);
    });

    /** Init cropper */
    function initCropper(modal, refresh = false) {
        let idModal = modal.attr('id');
        let wrap = modal.find('.cropper-wrap');
        let image = wrap.find('.image');
        let dataWidth = wrap.data('width');
        let dataHeight = wrap.data('height');
        let preview = wrap.find('.img-preview');
        if (refresh) {
            image.cropper('destroy');
            image = wrap.find('.image');
        }
        if (parseInt(dataWidth) === 0) {
            dataWidth = '';
        }
        if (parseInt(dataHeight) === 0) {
            dataHeight = '';
        }
        let fieldX = wrap.find('.dataX');
        let fieldY = wrap.find('.dataY');
        let fieldWidth = wrap.find('.dataWidth');
        let fieldHeight = wrap.find('.dataHeight');
        let fieldRotate = wrap.find('.dataRotate');
        let fieldScaleX = wrap.find('.dataScaleX');
        let fieldScaleY = wrap.find('.dataScaleY');
        let txtWidth = wrap.find('.txtWidth');
        let txtHeight = wrap.find('.txtHeight');
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
            preview: preview.attr('class'),
            zoomOnWheel: true,
            crop: function (e) {
                let modalINJS = document.querySelector('#' + idModal);
                let canvasINJS = modalINJS.querySelector('.cropper-canvas');
                tempContainerDataHeight = canvasINJS.offsetHeight;
                tempContainerDataWidth = canvasINJS.offsetWidth;
                fieldX.val(Math.round(e.x));
                fieldY.val(Math.round(e.y));
                fieldWidth.val(Math.round(e.width));
                fieldHeight.val(Math.round(e.height));
                fieldRotate.val(e.rotate);
                fieldScaleX.val(e.scaleX);
                fieldScaleY.val(e.scaleY);
                txtWidth.text(Math.round(e.width));
                txtHeight.text(Math.round(e.height));
                //TEMP FOR CORNER CALC
                tempImageHeight = Math.round(e.height);
                tempImageWidth = Math.round(e.width);
                tempOffsetX = Math.round(e.x);
                tempOffsetY = Math.round(e.y);
            },
            cropend: function (e) {
                //CALC CORNER
                let inLeftCorner = tempOffsetX < 2;
                let inTopCorner = tempOffsetY < 2;
                let inBottomCorner = tempOffsetY + tempImageHeight >= (tempContainerDataHeight - 2);
                let inRightCorner = tempOffsetX + tempImageWidth >= (tempContainerDataWidth - 2);
                //REWRITE IF COLLAPSED BORDER
                if (inLeftCorner || inTopCorner || inBottomCorner || inRightCorner) {
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
                    image.cropper("setData", {
                        "x": xPosition,
                        "y": yPosition,
                        "width": widthImage,
                        "height": heightImage
                    });
                }
            }
        };
        if (dataWidth !== "" && dataHeight !== "") {
            let ratio = dataWidth / dataHeight;
            options['aspectRatio'] = ratio;
        } else if (dataWidth === "" && dataHeight !== "") {
            options['aspectRatio'] = 16 / 9;
        } else if (dataWidth !== "" && dataHeight === "") {
            options['aspectRatio'] = 9 / 16;
        }
        image.cropper(options);
        let moveImg = wrap.find('.move-img');
        moveImg.on('click', function () {
            image.cropper("setDragMode", "move");
        });
        let cropImg = wrap.find('.move-img');
        cropImg.on('click', function () {
            image.cropper("setDragMode", "crop");
        });
        let zoomIn = wrap.find('.zoom-in');
        zoomIn.on('click', function () {
            image.cropper("zoom", 0.1);
        });
        let zoomOut = wrap.find('.zoom-out');
        zoomOut.on('click', function () {
            image.cropper("zoom", -0.1);
        });
        let moveLeft = wrap.find('.move-left');
        moveLeft.on('click', function () {
            image.cropper("move", -10, 0);
        });
        let moveRight = wrap.find('.move-right');
        moveRight.on('click', function () {
            image.cropper("move", 10, 0);
        });
        let moveUp = wrap.find('.move-up');
        moveUp.on('click', function () {
            image.cropper("move", 0, -10);
        });
        let moveDown = wrap.find('.move-down');
        moveDown.on('click', function () {
            image.cropper("move", 0, 10);
        });
        let rotateLeft = wrap.find('.rotate-left');
        rotateLeft.on('click', function () {
            image.cropper("rotate", -90);
        });
        let rotateRight = wrap.find('.rotate-right');
        rotateRight.on('click', function () {
            image.cropper("rotate", 90);
        });
        let flipHorizontal = wrap.find('.flip-horizontal');
        flipHorizontal.on('click', function () {
            let el = $(this);
            let scale = el.data('scale');
            let resetScale = scale === -1 ? 1 : -1;
            el.data('scale', resetScale);
            image.cropper("scaleX", scale);
        });
        let flipVertical = wrap.find('.flip-vertical');
        flipVertical.on('click', function () {
            let el = $(this);
            let scale = el.data('scale');
            let resetScale = scale === -1 ? 1 : -1;
            el.data('scale', resetScale);
            image.cropper("scaleY", scale);
        });
    }
});