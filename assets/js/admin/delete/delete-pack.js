import '../../../scss/admin/lib/sweetalert.scss';
import '../lib/sweetalert/sweetalert.min';
import displayAlert from "../core/alert";

/**
 * Delete pack
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    function setRows(element) {
        let parentRow = element.closest('.parent-row');
        if (typeof parentRow.data('level') !== 'undefined') {
            if (element.is(':checked')) {
                $('ol', parentRow).children('.parent-row').each(function () {
                    $(this).find('.delete-pack').prop('checked', true);
                });
            } else {
                parentRow.parents('.parent-row').each(function () {
                    $(this).children('.dd3-content').find('.delete-pack').prop('checked', false);
                });
            }
        }
    }

    /** Check elements to delete for hide or display deletion btn */
    function showBtn() {

        let hideBtn = true;
        $('.delete-pack').each(function () {
            let isCurrentCheck = $(this).is(':checked');
            if (isCurrentCheck) {
                hideBtn = false;
            }
        });

        let deleteBtn = $('#delete-pack-btn');
        if (hideBtn) {
            deleteBtn.addClass('d-none');
        } else {
            deleteBtn.removeClass('d-none');
        }
    }

    function removeItems() {

        let body = $('body');

        body.on('click', '#delete-pack-btn', function (e) {

            e.preventDefault();

            $('#delete-pack-btn').addClass('d-none');

            let trans = $('#data-translation');

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

                $('.delete-pack').each(function () {

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
                                if (response.alert && response.alert === 'error') {
                                    displayAlert(response.message, 'danger', null, false);
                                    $("html, body").animate({ scrollTop: 0 }, "slow");
                                } else {
                                    el.closest('li.parent-row').fadeOut(200);
                                    el.closest('.delete-pack-parent-row').fadeOut(200);
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
                });

                // swal(trans.data('deletion-completed'), "", "success");

                setTimeout(function () {
                    swal.close();
                }, 1500);
            });

            e.stopImmediatePropagation();
            return false;
        });
    }

    $(function () {

        $('.delete-pack').prop('checked', false);

        $('body').on('change', '.delete-pack', function (e) {
            setRows($(this));
            showBtn();
            removeItems();
        });
    });
}