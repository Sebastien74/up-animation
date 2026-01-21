/**
 * Charts
 */
export default function () {

    let body = $('body');
    let locale = $('html').attr('lang');
    let donuts = body.find('.donuts');

    donuts.each(function () {

        let donut = $(this);
        let items = donut.find('.data');

        if (!donut.hasClass('active')) {

            let data = [];
            items.each(function () {
                data.push({'label': $(this).data('label'), 'value': $(this).data('value')});
            });

            new Morris.Donut({
                element: donut.attr('id'),
                data: data,
                formatter: function (y, data) {
                    return y + donut.data('formatter')
                },
                resize: true
            });

            donut.addClass('active');
        }
    });

    let lines = body.find('.chart-lines');
    lines.each(function () {

        let line = $(this);
        let xKey = line.data('x-key');
        let yKeys = line.data('y-keys');
        let items = line.find('.data');
        let inTabActive = line.closest('.tab-pane.active');

        moment.locale(locale);

        if (inTabActive.length > 0 && !line.hasClass('active')) {

            let data = [];
            items.each(function () {
                let xValue = $(this).data('x-key');
                data.push({[xKey]: xValue.toString(), [yKeys]: $(this).data('y-key')});
            });

            new Morris.Line({
                element: line.attr('id'),
                data: data,
                xkey: xKey,
                ykeys: [yKeys],
                labels: [line.data('value')],
                hoverCallback: function (index, options, content, row) {
                    let label = '<div class="morris-hover-row-label">' + moment(row[xKey]).format(line.data('format')) + '</div>';
                    let info = '<div class="morris-hover-point"> ' + line.data('value') + ': ' + row[yKeys] + ' </div>';
                    return label + info;
                },
                xLabelFormat: function (date) {
                    return moment(date).format(line.data('format'));
                }
            });

            line.addClass('active');
        }
    });
}