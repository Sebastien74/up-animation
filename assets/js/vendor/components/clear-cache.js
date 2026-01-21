import route from "./routing";

/**
 * Clear cache
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (html) {
    let nonce = html.data('nonce');
    if (nonce) {
        setTimeout(function () {
            $.ajax({
                url: route('front_clear_cache') + '?token=' + nonce,
                type: "GET",
                processData: false,
                contentType: false,
                dataType: 'json',
                async: true,
                beforeSend: function () {
                },
                success: function (response) {
                },
                error: function (errors) {
                }
            });
        }, 3000);
    }
};