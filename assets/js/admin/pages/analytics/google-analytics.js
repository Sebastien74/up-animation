import '../../../../scss/admin/pages/google-analytics.scss';

(function (w, d, s, g, js, fs) {
    g = w.gapi || (w.gapi = {});
    g.analytics = {
        q: [], ready: function (f) {
            this.q.push(f);
        }
    };
    js = d.createElement(s);
    fs = d.getElementsByTagName(s)[0];
    js.src = 'https://apis.google.com/js/platform.js';
    fs.parentNode.insertBefore(js, fs);
    js.onload = function () {
        g.load('analytics');
    };
}(window, document, 'script'));

gapi.analytics.ready(function () {

    let elData = $('#google-data-js');
    let GaID = elData.data('account-id'); // Client Google Analytics Account ex : ga:XXXXXXX
    let duration = elData.data('duration') ? elData.data('duration') : '30daysAgo';
    let CLIENT_ID = elData.data('client-id');

    /**
     *  Google Auth.
     */
    gapi.analytics.auth.authorize({
        clientid: CLIENT_ID, // ex : xxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com
        container: 'embed-api-auth-container'
    });

    /**
     * Create a ViewSelector for the first view to be rendered inside of an
     * element with the id "view-selector-1-container".
     */
    let viewSelector = new gapi.analytics.ViewSelector({
        container: 'view-selector-container'
    });

    let chartFunction = function (GaID, duration, metric, container, dimensions = 'ga:date') {
        return new gapi.analytics.googleCharts.DataChart({
            query: {
                ids: GaID,
                metrics: metric,
                dimensions: dimensions,
                'start-date': duration,
                'end-date': 'today',
                sort: '-' + metric,
            },
            chart: {
                container: container,
                type: 'LINE',
                options: {
                    width: '100%',
                    is3D: true
                }
            }
        });
    };

    let pieChartFunction = function (GaID, duration, metric, dimensions, container, maxResult = 10) {
        return new gapi.analytics.googleCharts.DataChart({
            query: {
                ids: GaID,
                metrics: metric,
                dimensions: dimensions,
                sort: '-' + metric,
                'start-date': duration,
                'end-date': 'today',
                'max-results': maxResult
            },
            chart: {
                container: container,
                type: 'PIE',
                options: {
                    width: '100%',
                    pieHole: 4 / 9,
                    is3D: true
                }
            }
        });
    };

    let geoChartFunction = function (GaID, duration, metric, dimensions, container) {
        return new gapi.analytics.googleCharts.DataChart({
            query: {
                ids: GaID,
                metrics: metric,
                dimensions: dimensions,
                'start-date': duration,
                'end-date': 'today'
            },
            chart: {
                container: container,
                type: 'GEO',
                options: {
                    width: '100%'
                }
            }
        });
    };

    let tableChartFunction = function (GaID, duration, metric, dimensions, container) {
        return new gapi.analytics.googleCharts.DataChart({
            query: {
                ids: GaID,
                metrics: metric,
                dimensions: dimensions,
                'start-date': duration,
                'end-date': 'today',
                sort: '-' + metric
            },
            chart: {
                container: container,
                type: 'TABLE',
                options: {
                    width: '100%'
                }
            }
        });
    };

    gapi.analytics.auth.on('success', function (response) {
        viewSelector.execute();
        $('#google-params').removeClass('d-none');
    });

    /**
     * Update dataChart
     */
    viewSelector.on('change', function (ids) {

        $('#gaIdResult').remove();
        let resultEl = $('#gaIdResultContainer');
        let html = '<span id="gaIdResult" class="badge badge-info mt-3">Analytics account ID : ' + ids + '</span>';
        resultEl.append(html);

        /** To display the number of sessions per day */
        if ($('#sessions-chart').length) {
            let sessionsChart = chartFunction(ids, duration, 'ga:sessions', 'sessions-chart');
            sessionsChart.set({query: {ids: ids}}).execute();
        }

        /** To display the rebound per day */
        if ($('#rebound-chart').length) {
            let reboundChart = chartFunction(ids, duration, 'ga:bounceRate', 'rebound-chart');
            reboundChart.set({query: {ids: ids}}).execute();
        }

        /** To display the session duration per day */
        if ($('#duration-chart').length) {
            let durationChart = chartFunction(ids, duration, 'ga:sessionDuration', 'duration-chart');
            durationChart.set({query: {ids: ids}}).execute();
        }

        /** To display the percent of new Users type */
        if ($('#new-users-chart').length) {
            let usersChart = pieChartFunction(ids, duration, 'ga:newUsers', 'ga:userType', 'new-users-chart');
            usersChart.set({query: {ids: ids}}).execute();
        }

        /** To display the percent of screen size visit */
        if ($('#size-chart').length) {
            let sizesChart = pieChartFunction(ids, duration, 'ga:sessions', 'ga:browserSize', 'size-chart');
            sizesChart.set({query: {ids: ids}}).execute();
        }

        /** To display the number of sessions per country */
        if ($('#country-chart').length) {
            let countriesChart = pieChartFunction(ids, duration, 'ga:sessions', 'ga:country', 'country-chart', 4);
            countriesChart.set({query: {ids: ids}}).execute();
        }

        /** To display the number of sessions per city */
        if ($('#city-chart').length) {
            let citiesChart = pieChartFunction(ids, duration, 'ga:sessions', 'ga:city', 'city-chart');
            citiesChart.set({query: {ids: ids}}).execute();
        }

        /** To display the number of sessions per city */
        if ($('#geo-chart').length) {
            let geoChart = geoChartFunction(ids, duration, 'ga:sessions', 'ga:country', 'geo-chart');
            geoChart.set({query: {ids: ids}}).execute();
        }

        /** To display the number of pages views per day */
        if ($('#pages-chart').length) {
            let pagesChart = chartFunction(ids, duration, 'ga:pageviews', 'pages-chart');
            pagesChart.set({query: {ids: ids}}).execute();
        }

        /** To display the source of the Internet search */
        if ($('#pages-tab-chart').length) {
            let pagesTabChart = chartFunction(ids, duration, 'ga:pageviews', 'pages-tab-chart', 'ga:pageTitle');
            pagesTabChart.set({query: {ids: ids}}).execute();
        }

        /** To display the source of the Internet search */
        if ($('#search-tab-chart').length) {
            let searchTabChart = tableChartFunction(ids, duration, 'ga:organicSearches', 'ga:source', 'search-tab-chart');
            searchTabChart.set({query: {ids: ids}}).execute();
        }
    });
});