import './leaflet'
import './leaflet.markercluster'
import '../../../../../scss/front/default/components/map/_map.scss'

/**
 * Open street map
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (maps, autoCenterMap = null) {

    let body = document.body;
    let screenWidth = window.screen.width;
    let geometryZones = document.querySelector('.map-point-zone');
    let geoJsonData = null;

    let centerMap = function(map) {
        map.invalidateSize();
    }

    // Geometry zones
    if (geometryZones) {
        Promise.all([
            fetch('/geo-json/geo-lite.json').then(res => res.json())
        ]).then(([geoData]) => {
            geoJsonData = {
                type: "FeatureCollection",
                features: [...geoData.features]
            };
        });
    }

    let highlightedLayer = null;

    function clearHighlightedZones(map) {
        if (highlightedLayer) {
            map.removeLayer(highlightedLayer);
            highlightedLayer = null;
        }
    }

    function highlightZones(map, zoneCodes = {}, color = '#ff002d') {
        clearHighlightedZones(map);
        if (!geoJsonData) return;
        let zoneCodesArray = JSON.parse(zoneCodes);
        let featuresToHighlight = geoJsonData.features.filter(feature => {
            const code = feature.properties.code;
            const isCorsica = (code === '2A' || code === '2B') && zoneCodesArray.includes(20);
            return zoneCodesArray.includes(code) || isCorsica;
        });
        if (featuresToHighlight.length > 0) {
            highlightedLayer = L.geoJSON(featuresToHighlight, {
                style: {
                    color: color,
                    weight: 0,
                    fillColor: color,
                    fillOpacity: 0.4
                }
            }).addTo(map);
        }
    }

    let iniMap = function (mapBox) {

        mapBox.classList.add('initialized');

        let allMarkers = [];
        let mapContainer = mapBox.closest('.map-container');
        let loader = mapContainer.querySelector('.loader');
        let mapBoxId = mapBox.getAttribute('id');
        let layerUrl = mapBox.dataset.layer ? mapBox.dataset.layer : 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
        let data = mapBox.querySelector('.data-map');
        let isMultiple = mapBox.dataset.multiple;
        let haveClusters = parseInt(data.dataset.markerClusters) === 1;
        let autoCenter = typeof data.dataset.autoCenter != 'undefined' ? parseInt(data.dataset.autoCenter) === 1 : false;
        let forceZoom = typeof data.dataset.forceZoom != 'undefined' ? parseInt(data.dataset.forceZoom) === 1 : false;
        let popupHover = typeof data.dataset.popupHover != 'undefined' ? parseInt(data.dataset.popupHover) === 1 : false;
        if (autoCenterMap) {
            autoCenter = parseInt(autoCenterMap) === 1;
        }

        if (mapBoxId) {
            let container = L.DomUtil.get(mapBoxId);
            if (container != null) {
                container._leaflet_id = null;
            }
        }

        let map = L.map(mapBox.getAttribute('id'), {
            center: [data.dataset.latitude, data.dataset.longitude],
            zoom: parseInt(data.dataset.zoom),
            zoomControl: true, /** false = no zoom control buttons displayed */
            keyboard: true,
            scrollWheelZoom: false, /** false = scrolling zoom on the map is locked */
            dragging: !L.Browser.mobile
        });

        L.tileLayer(layerUrl, {
            minZoom: parseInt(data.dataset.minZoom),
            maxZoom: parseInt(data.dataset.maxZoom),
            attribution: '<a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributor',
            tap: false,
            crossOrigin: true,
        }).addTo(map);

        let markerClusters;
        let cloneMarkerCluster;
        if (haveClusters) {
            markerClusters = L.markerClusterGroup();
            cloneMarkerCluster = markerClusters;
        }

        data.querySelectorAll('.point').forEach(point => {

            let marker;
            let markerWidth = parseInt(point.dataset.markerWidth);
            let markerHeight = parseInt(point.dataset.markerHeight);
            let inCluster = parseInt(data.dataset.inCluster) === 1;
            let geoJson = typeof point.dataset.geoJson != 'undefined' && point.dataset.geoJson ? point.dataset.geoJson : false;

            let icon = L.icon({
                iconUrl: point.dataset.marker,
                iconSize: [markerWidth, markerHeight],
                className: point.dataset.category + ' point-' + point.dataset.id,
                iconAnchor: [(markerWidth / 2), markerHeight], /** Marker position: ([icon]width / 2) & [icon]height */
                popupAnchor: [0, -markerWidth] /** Popup position */
            });

            if (!haveClusters && !inCluster) {
                marker = L.marker([point.dataset.latitude, point.dataset.longitude], {icon: icon}).addTo(map);
            } else {
                marker = L.marker([point.dataset.latitude, point.dataset.longitude], {icon: icon});
                markerClusters.addLayer(marker)
            }

            allMarkers.push(marker);

            if (geoJson) {
                fetch(geoJson)
                    .then(res => res.json())
                    .then(trackData => {
                        const trackLayer = L.geoJSON(trackData, {
                            style: {
                                color: '#0055ff',
                                weight: 4,
                                opacity: 0.9
                            }
                        }).addTo(map);
                        // Zoom automatique sur le tracé
                        map.fitBounds(trackLayer.getBounds(), {
                            padding: [40, 40],
                            maxZoom: 14
                        });
                    })
                    .catch(err => {
                        console.error('Erreur lors du chargement du tracé :', err);
                    });
            }

            html(point, data, marker);
        });

        map.scrollWheelZoom.disable();
        map.doubleClickZoom.disable();

        if (haveClusters) {
            map.addLayer(markerClusters);
        }

        if (autoCenter && allMarkers.length > 0) {
            const group = L.featureGroup(allMarkers);
            const bounds = group.getBounds();
            const wantedZoom = parseInt(data.dataset.zoom, 10);
            const padding = L.point(50, 50);
            if (forceZoom && Number.isFinite(wantedZoom)) {
                const fitZoom = map.getBoundsZoom(bounds, true, padding);
                const finalZ = Math.min(fitZoom, wantedZoom);
                map.flyTo(bounds.getCenter(), finalZ, {duration: 1});
            } else {
                map.flyToBounds(bounds, {duration: 1, padding: [50, 50], maxZoom: wantedZoom})
            }
        }

        if (screenWidth > 991) {
            map.getContainer().addEventListener('wheel', function (e) {
                if (map._zooming) {
                    e.preventDefault();
                }
            }, { passive: false });
            // map.on('zoomstart', function() {
            //     let menuHeight = document.querySelector('#main-navigation').clientHeight;
            //     let offset = mapBox.getBoundingClientRect().top + window.scrollY - menuHeight;
            //     window.scrollTo({top: offset, behavior: 'smooth'});
            // });
        }

        map.on('popupopen', function (ev) {
            body.classList.add('maker-open');
            let popup = ev.target._popup;
            /** To center popup and marker in map center **/
            /** find the pixel location on the map where the popup anchor is */
            let px = map.project(popup._latlng);
            /** find the height of the popup container, divide by 2, subtract from the Y axis of marker location */
            px.y -= popup._container.clientHeight / 2;
            /** pan to new center */
            map.panTo(map.unproject(px), {animate: true});
            let popupEL = document.createElement('div');
            popupEL.innerHTML = popup._content;
            popupEL = popupEL.querySelector('.point-popup-html');
            popupEL = document.getElementById(popupEL.getAttribute('id'));
            if (popupEL && popupEL.classList.contains('refresh')) {
                popupEL.onclick = function () {
                    if (loader) {
                        loader.classList.remove('d-none');
                    }
                    let xHttp = new XMLHttpRequest();
                    xHttp.open("GET", popupEL.dataset.path, true);
                    xHttp.send();
                    xHttp.onload = function () {
                        if (this.readyState === 4 && this.status === 200) {
                            let response = this.response;
                            response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
                            response = JSON.parse(response);
                            let sidebar = mapContainer.querySelector('.map-sidebar');
                            let box = mapContainer.querySelector('.map-filter-box');
                            let boxSize = 12 - parseInt(box.dataset.sidebarSize);
                            box.classList.remove('col-lg-12');
                            box.classList.add('col-lg-' + boxSize);
                            sidebar.classList.remove('d-none');
                            sidebar.innerHTML = response.html;
                            let hx = document.querySelector('hx\\:include');
                            if (hx) {
                                import('../../../../vendor/components/medias-loader').then(({default: mediaLoader}) => {
                                    new mediaLoader();
                                }).catch(error => console.error(error.message));
                            }
                            let loader = mapContainer.querySelector('.loader');
                            if (loader) {
                                loader.classList.add('d-none');
                            }
                            centerMap(map);
                        }
                    }
                }
            }
        });

        map.on('popupclose', function () {
            body.classList.remove('maker-open');
        })

        let mapLoaded = function () {
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Tab') {
                    setTimeout(() => {
                        const focusedEl = document.activeElement;
                        if (focusedEl === mapBox) {
                            mapBox.addEventListener('keydown', (e) => {
                                if (focusedEl !== mapBox) return;
                                if (e.key === '+' || e.key === '=' || e.key === 'Add') {
                                    map.zoomIn();
                                    e.preventDefault();
                                }
                                if (e.key === '-' || e.key === 'Subtract') {
                                    map.zoomOut();
                                    e.preventDefault();
                                }
                            });
                        }
                    });
                }
            });
        }
        map.whenReady(mapLoaded);

        /** To localize user */
        // map.locate({
        //     watch: true,
        //     setView: true,
        //     maxZoom: parseInt(data.dataset.maxZoom),
        //     enableHighAccuracy: true,
        //     maximumAge: 10000,
        //     frequency: 1
        // });

        /**
         * To manage categories filters
         */
        let categoriesFilters = function () {

            let filters = document.body.getElementsByClassName('map-filter-checkbox')
            for (let i = 0; i < filters.length; i++) {
                filters[i].onclick = function () {
                    filters[i].closest('.marker-select').click();
                }
            }

            let selects = document.body.getElementsByClassName('marker-select');

            for (let i = 0; i < selects.length; i++) {

                let el = selects[i];

                el.onclick = function (e) {

                    if (!e.target.classList.contains('map-point-filter')) {

                        let filterMarkers = [];
                        if (isMultiple) {
                            el.classList.toggle('active');
                        } else {
                            let markerSelects = document.body.getElementsByClassName('marker-select');
                            for (let j = 0; j < markerSelects.length; j++) {
                                markerSelects[j].classList.remove('active');
                            }
                            el.classList.add('active');
                        }

                        clearMap();

                        let markerSelectsActive = document.body.querySelectorAll('.marker-select.active');

                        for (let j = 0; j < markerSelectsActive.length; j++) {
                            let filter = markerSelectsActive[j];
                            let filterCategory = filter.dataset.category;
                            addToLayer(filterMarkers, filterCategory);
                        }

                        if (filterMarkers.length > 0) {
                            let markers = new L.featureGroup(filterMarkers);
                            // map.fitBounds(markers.getBounds(), { padding: [50, 50] })
                            map.flyToBounds(markers.getBounds(), {
                                padding: [50, 50],
                                duration: 1,
                                maxZoom: parseInt(data.dataset.zoom)
                            });
                            if (haveClusters) {
                                map.addLayer(markerClusters);
                            }
                        }

                        resetFilter(markerSelectsActive, selects);
                    }
                }
            }
        }

        /**
         * To manage points filters
         */
        let pointsFilters = function () {
            let mapContainer = mapBox.closest('.map-container');
            let filterPoints = mapContainer.getElementsByClassName('map-point-filter');
            for (let j = 0; j < filterPoints.length; j++) {
                let filterPoint = filterPoints[j];
                filterPoint.onclick = function () {
                    clearMap();
                    /** To add in Layer and zoom to point */
                    let filterMarkers = [];
                    let pointId = filterPoint.dataset.id;
                    addToLayer(filterMarkers, pointId, true);
                    let markers = new L.featureGroup(filterMarkers);
                    map.flyToBounds(markers.getBounds(), {
                        padding: [50, 50],
                        duration: 1,
                        maxZoom: parseInt(data.dataset.zoom)
                    });
                    /** Filters display */
                    // let
                    // map.find('.associated-points-list:not(.active)').find('.map-point-filter').addClass('active');
                }
            }
        }

        categoriesFilters();
        pointsFilters();

        /**
         * To clear map
         */
        function clearMap() {
            map.closePopup();
            if (haveClusters) {
                cloneMarkerCluster.clearLayers();
            } else {
                map.eachLayer((layer) => {
                    if (typeof layer._url == 'undefined') {
                        layer.remove();
                    }
                })
            }
        }

        /**
         * To add marker to Layer
         */
        let addToLayer = function (filterMarkers, filter, asPoint = false) {
            for (let k = 0; k < allMarkers.length; k++) {
                let markerObj = allMarkers[k];
                let markerClassName = markerObj.options.icon.options.className;
                let markerClassNames = markerClassName.split('point-');
                let markerCategory = 0 in markerClassNames ? markerClassNames[0].trim() : null;
                let markerPointId = 1 in markerClassNames ? markerClassNames[1].trim() : null;
                let markerFilter = asPoint ? markerPointId : markerCategory;
                if (markerFilter === filter) {
                    filterMarkers.push(markerObj);
                    if (haveClusters) {
                        markerClusters.addLayer(markerObj);
                    } else {
                        map.addLayer(markerObj);
                    }
                }
            }
        }

        /**
         * To manage reset filter
         */
        function resetFilter(markerSelectsActive, selects) {

            let resetFilterEls = mapBox.closest('.map-container').getElementsByClassName('reset-filter');
            let resetFilterEl = resetFilterEls.length > 0 ? resetFilterEls[0] : null;

            if (resetFilterEl) {

                if (resetFilterEl && markerSelectsActive.length < selects.length) {
                    resetFilterEl.classList.remove('d-none');
                } else {
                    resetFilterEl.classList.add('d-none');
                }

                resetFilterEl.onclick = function () {

                    clearMap();

                    let markers = new L.featureGroup(allMarkers);
                    // map.fitBounds(markers.getBounds(), { padding: [50, 50] })
                    map.flyToBounds(markers.getBounds(), {
                        padding: [50, 50],
                        duration: 1,
                        maxZoom: parseInt(data.dataset.zoom)
                    });

                    for (let j = 0; j < allMarkers.length; j++) {
                        if (haveClusters) {
                            markerClusters.addLayer(allMarkers[j]);
                        } else {
                            map.addLayer(allMarkers[j]);
                        }
                    }
                    if (haveClusters) {
                        map.addLayer(markerClusters);
                    }

                    let selectsEl = document.body.getElementsByClassName('marker-select');
                    for (let j = 0; j < selectsEl.length; j++) {
                        let select = selects[j];
                        if (!select.classList.contains('active')) {
                            select.classList.add('active');
                        }
                    }
                }
            }
        }

        /**
         * To manage HTML
         */
        function html(point, data, marker) {

            let html = '';
            let display = false;
            let htmlInViewEl = point.querySelector('.point-popup-html');

            if (htmlInViewEl) {
                let elHTML = htmlInViewEl;
                elHTML.classList.remove('d-none');
                html += elHTML.outerHTML;
                display = true;
                // elHTML.remove(); // Comment ton icon with style blocked by CSP
            } else {
                // JS HTML POPUP;
            }

            if (display) {
                marker.bindPopup(html);
                if (popupHover) {
                    marker.on('mouseover', function (e) {
                        this.openPopup();
                    });
                }
                if (geometryZones) {
                    marker.on('click', () => {
                        highlightZones(map, point.dataset.zones, point.dataset.color);
                    });
                }
                L.popup({
                    autoClose: true
                });
            }
        }
    }

    maps.forEach(mapBox => {
        if (!mapBox.classList.contains('initialized')) {
            iniMap(mapBox);
        }
    });

    body.classList.add('map-initialized');
}