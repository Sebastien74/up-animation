/** Import CSS */
import '../../../../scss/front/default/templates/catalog.scss';

/** Import JS */
import {isInViewport} from "../functions";
import "../components/remove-empty-blocks";

/** Map */
let maps = document.querySelectorAll('.map-box');
if (maps.length > 0) {
    let mapModule = function () {
        import('../components/map/map').then(({default: mapModule}) => {
            new mapModule(maps);
            document.body.classList.add('map-initialized');
        }).catch(error => console.error(error.message));
    }
    if (isInViewport(maps[0])) {
        mapModule();
    } else {
        window.addEventListener('scroll', function (e) {
            mapModule();
        });
    }
}