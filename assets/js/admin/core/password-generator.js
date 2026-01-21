import route from "../../vendor/components/routing";

/**
 * Password generator
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (event, el) {

    let spinnerIcon = $(el).find('svg');
    let referCopy = $('body').find('.refer-copy');

    $.ajax({
        url: route('security_password_generator'),
        type: "GET",
        processData: false,
        contentType: false,
        dataType: 'json',
        async: true,
        beforeSend: function () {
            if (!referCopy.hasClass('d-none')) {
                referCopy.toggleClass('d-none');
            }
            spinnerIcon.toggleClass('fa-spin');
        },
        success: function (response) {
            if (response.password) {
                referCopy.toggleClass('d-none');
                $('body').find('.to-copy').text(response.password);
                spinnerIcon.toggleClass('fa-spin');
            }
        },
        error: function (errors) {
            /** Display errors */
            import('../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
        }
    });

    event.stopImmediatePropagation();
    return false;
}