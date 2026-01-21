(function (d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) {
        return;
    }
    js = d.createElement(s);
    js.id = id;
    js.src = "https://connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

let body = document.body;
let userRegistrationForm = document.getElementById('user-registration-form')

window.fbAsyncInit = function () {

    FB.init({
        appId: '1399120140488211',
        cookie: true,
        xfbml: true,
        version: 'v11.0'
    })

    FB.AppEvents.logPageView()

    FB.Event.subscribe('auth.statusChange', function(response) {
        statusChangeCallback(response)
        // if(response.status === 'connected') {
        //     // `connected` means that the user is logged in and that your app is authorized to do requests on the behalf of the user
        //     afterLogin();
        // } else if(response.status === 'not_authorized') {
        //     // The user is logged in on Facebook, but has not authorized your app
        // } else {
        //     // The user is not logged in on Facebook
        // }
    })

    FB.getLoginStatus(function (response) {
        statusChangeCallback(response)
    })

    // function checkLoginState() {
    //     FB.getLoginStatus(function(response) {
    //         statusChangeCallback(response)
    //     })
    // }

    function statusChangeCallback(response) {
        console.log(response)
    }
}