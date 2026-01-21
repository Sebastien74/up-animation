import 'nestable2';

/**
 * Nestable
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let body = $('body');
    let isActive = body.hasClass('editor');

    $('.nestable-list-container').each(function () {

        let el = $(this);
        let elId = el.attr('id');
        let outputField = el.data('output-field');
        let limit = el.data('limit');

        /** Nestable */
        let updateOutput = function (e) {

            let body = $('body');
            let windowLoadEl = body.find('.nestable-window-load');
            let isFirstLoad = windowLoadEl.length <= 0;
            let list = e.length ? e : $(e.target),
                output = list.data('output');
            let form = el.find('form.nestable-outpout-form');
            let formID = form.attr('id');
            let preloader = body.find('#nestable-list-preloader');

            if (typeof output != "undefined" && typeof formID != "undefined") {

                output.val(JSON.stringify(list.nestable('serialize'))); //, null, 2));

                let formData = new FormData(document.getElementById(formID));

                $.ajax({
                    url: form.attr('action'),
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    async: true,
                    beforeSend: function () {
                        if (!isFirstLoad) {
                            preloader.removeClass('d-none');
                        }
                    },
                    success: function (response) {
                        if (!isFirstLoad) {
                            preloader.addClass('d-none');
                        }
                    },
                    error: function (errors) {
                        /** Display errors */
                        import('../core/errors').then(({default: displayErrors}) => {
                            new displayErrors(errors);
                        }).catch(error => console.error(error.message));
                    }
                });
            }
        };

        if (isActive) {

            let nestableEl = $('#' + elId);

            body.on('click', '.delete-pack', function () {
                if (!nestableEl.hasClass('disabled-nestable')) {
                    nestableEl.addClass('disabled-nestable');
                }
            });

            nestableEl.nestable({
                maxDepth: limit
            });

            nestableEl.on('change', function () {
                let element = $('#' + elId);
                if (!element.hasClass('disabled-nestable')) {
                    updateOutput(element.data('output', $(outputField)));
                }
                element.removeClass('disabled-nestable');
            });
        }

        /** To use loader only if not first load */
        el.append('<span class="nestable-window-load"></span>');
    });

    let mouseY;
    let speed = 0.15;
    let zone = 50;

    $(document).mousemove(function (e) {
        mouseY = e.pageY - $(window).scrollTop();
    }).mouseover();

    let dragInterval = setInterval(function () {

        if ($('.dd-dragel') && $('.dd-dragel').length > 0 && !$('html, body').is(':animated')) {

            let bottom = $(window).height() - zone;

            if (mouseY > bottom && ($(window).scrollTop() + $(window).height() < $(document).height() - zone)) {
                $('html, body').animate({scrollTop: $(window).scrollTop() + ((mouseY + zone - $(window).height()) * speed)}, 0);
            } else if (mouseY < zone && $(window).scrollTop() > 0) {
                $('html, body').animate({scrollTop: $(window).scrollTop() + ((mouseY - zone) * speed)}, 0);

            } else {
                $('html, body').finish();
            }
        }
    }, 16);

    /** Collapsed items event */
    $('.btn-collapsed-group').each(function () {

        let group = $(this);
        let collapseBtn = group.find('.collapse-btn');
        let expandBtn = group.find('.expand-btn');

        collapseBtn.on('click', function () {
            collapseBtn.toggleClass('d-none');
            expandBtn.toggleClass('d-none');
            expandBtn.removeClass('active');
        });

        expandBtn.on('click', function () {
            expandBtn.toggleClass('d-none');
            collapseBtn.toggleClass('d-none');
            if (!expandBtn.hasClass('active')) {
                expandBtn.addClass('active');
            }
        });
    });

    /** Expand all */
    body.on('click', '#nestable-expand-all', function () {
        let expandBtn = $('body').find('.expand-btn');
        expandBtn.trigger('click');
        if (!expandBtn.hasClass('active')) {
            expandBtn.addClass('active');
        }
        $('#nestable-expand-all').addClass('d-none');
        $('#nestable-collapse-all').removeClass('d-none');
    });

    /** Collapse all */
    body.on('click', '#nestable-collapse-all', function () {
        let body = $('body');
        let collapseBtn = body.find('.collapse-btn');
        let expandBtn = body.find('.expand-btn');
        collapseBtn.click();
        expandBtn.removeClass('active');
        $('#nestable-expand-all').removeClass('d-none');
        $('#nestable-collapse-all').addClass('d-none');
    });
}