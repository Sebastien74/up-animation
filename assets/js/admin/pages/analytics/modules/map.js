/**
 * Vector MAp
 */
export default function () {

    let data = [];
    let worldMap = $('#world-map-markers');
    let items = worldMap.find('.data');

    items.each(function () {
        let item = $(this);
        let caption = item.data('label') + ' : ' + item.data('sessions');
        data.push({latLng: [parseFloat(item.data('latitude')), parseFloat(item.data('longitude'))], name: caption});
    });

    worldMap.vectorMap(
    {
        map: 'world_mill_en',
        backgroundColor: 'transparent',
        normalizeFunction: 'linear',
        regionStyle: {
            initial: {
                fill: '#eeeeee'
            }
        },
        markerStyle: {
            initial: {
                r: 9,
                'fill': '#fff',
                'fill-opacity': 1,
                'stroke': '#000',
                'stroke-width': 5,
                'stroke-opacity': 0.4
            },
        },
        zoomOnScroll: false,
        markers: data
    });
}