/**
 * Analytics
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import route from "../../../vendor/components/routing";

export default function () {

    window.addEventListener('beforeunload', (event) => {
        pageActivity();
        // KEEP CONSOLE.LOG !!!
        // console.log(event);
        // KEEP CONSOLE.LOG !!!
    });

    function pageActivity() {
        $.ajax({
            url: route('front_activity') + '?uri=' + JSON.stringify(window.location.pathname),
            type: "GET",
            async: true
        });
    }
}