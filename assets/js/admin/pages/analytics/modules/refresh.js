import route from "../../../../vendor/components/routing";
import charts from "./charts";

/**
 * Refresh page an generate cache
 */
export default function (body) {

    cache(body);
    // setInterval(function() {
    //     cache(body);
    // }, 60 * 1000);

    function cache(body) {

        let page = $('#analytics-page');

        $.ajax({
            url: route('admin_analytics_cache', {
                website: body.data('id'),
                startDate: page.data('start'),
                endDate: page.data('end')
            }),
            type: "GET",
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
            },
            success: function () {
                // getPage();
            },
            error: function (errors) {
                // displayErrors(error);
            }
        });
    };

    function getPage() {

        let top = document.documentElement.scrollTop;
        let path = window.location.href;
        let url = path + '?ajax=true';
        if (path.indexOf('?') > -1) {
            url = path + '&ajax=true'
        }

        $.ajax({
            url: url,
            type: "GET",
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
            },
            success: function (response) {
                if (response.html) {
                    let body = $('body');
                    let html = $(response.html).find('#charts-results')[0];
                    let ajaxContent = body.find('#charts-results');
                    ajaxContent.replaceWith(html);
                    charts(body);
                    $('html, body').animate({scrollTop: top}, 0);
                }
            },
            error: function (errors) {
                // displayErrors(error);
            }
        });
    };
}