import '../../../scss/admin/lib/sweetalert.scss';
import '../lib/sweetalert/sweetalert.min';

export default function () {

    let body = $('body');

    $(function () {

        let removeAllBtn = body.find('.delete-index-all');

        body.find('.delete-input-index').prop('checked', false);
        removeAllBtn.prop('checked', false);

        body.on('click', '.index-delete-show', function (e) {

            e.preventDefault();

            let el = $(this);
            let isActive = el.hasClass('active');
            let card = el.closest('.card');
            let inputs = card.find('.delete-input-index');
            let removeAllBtn = card.find('.delete-index-all');

            if (isActive) {
                inputs.parent().removeClass('d-inline-block').addClass('d-none');
                removeAllBtn.parent().addClass('d-none');
                el.attr('data-original-title', el.data('display')).tooltip();
                el.removeClass('active');
            } else {
                inputs.parent().removeClass('d-none').addClass('d-inline-block');
                removeAllBtn.parent().removeClass('d-none');
                el.attr('data-original-title', el.data('hide')).tooltip();
                el.addClass('active');
            }
        });

        let inputChecked = function (card) {

            let inputsChecked = card.find('.delete-input-index:checkbox:checked');
            let removeBtn = card.find('.index-delete-submit');
            let showBtn = card.find('.index-delete-show');

            if (inputsChecked.length > 0) {
                removeBtn.removeClass('d-none');
                showBtn.addClass('d-none');
            } else {
                removeBtn.addClass('d-none');
                showBtn.removeClass('d-none');
            }
        };

        body.on('change', '.delete-index-all', function (e) {

            let el = $(this);
            let card = el.closest('.card');
            let isChecked = card.find('.delete-index-all').is(':checked');
            let allInputs = card.find('.delete-input-index');
            let removeAllBtn = card.find('.delete-index-all');
            let parent = removeAllBtn.parent();

            if (isChecked) {
                parent.attr('data-original-title', parent.data('unchecked')).tooltip();
                allInputs.prop('checked', true);
            } else {
                parent.attr('data-original-title', parent.data('checked')).tooltip();
                allInputs.prop('checked', false);
            }

            inputChecked(card);
        });

        body.on('change', '.delete-input-index', function (e) {
            let card = $(this).closest('.card');
            inputChecked(card);
        });

        body.on('click', '.index-delete-submit', function (e) {

            e.preventDefault();

            let trans = $('#data-translation');
            let card = $(this).closest('.card');

            swal({
                title: trans.data('swal-delete-title'),
                text: trans.data('swal-delete-text'),
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: trans.data('swal-delete-confirm-text'),
                cancelButtonText: trans.data('swal-delete-cancel-text'),
                closeOnConfirm: false
            }, function () {

                body.find('.sa-button-container .confirm').attr('disabled', '');
                body.find('.sa-button-container .cancel').attr('disabled', '');

                card.find('.delete-input-index').each(function () {

                    let el = $(this);

                    if (el.is(':checked')) {

                        let path = el.data('path');
                        let url = path + '?ajax=true';
                        if (path.indexOf('?') > -1) {
                            url = path + '&ajax=true'
                        }

                        $.ajax({
                            url: url,
                            type: "DELETE",
                            processData: false,
                            contentType: false,
                            async: true,
                            dataType: 'json',
                            beforeSend: function () {
                            },
                            success: function (response) {
                                el.closest('tr').fadeOut(200);
                            },
                            error: function (errors) {

                                /** Display errors */
                                import('../core/errors').then(({default: displayErrors}) => {
                                    new displayErrors(errors);
                                }).catch(error => console.error(error.message));
                            }
                        });
                    }
                });

                setTimeout(function () {
                    swal.close();
                }, 1500);

                let inputs = card.find('.delete-input-index');
                let removeBtn = card.find('.index-delete-submit');
                let showBtn = card.find('.index-delete-show');

                removeBtn.addClass('d-none');
                showBtn.removeClass('d-none').attr('data-original-title', showBtn.data('display')).tooltip();
                inputs.parent().addClass('d-none').removeClass('d-inline-block');
            });

            e.stopImmediatePropagation();
            return false;
        });
    });
}