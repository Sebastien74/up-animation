export default function(uri) {

    if(uri) {
        history.pushState({}, null, uri);
    }
    else {
        let uri = window.location.toString();
        let cleanUri = uri.substring(0, uri.indexOf("?"));
        window.history.replaceState({}, document.title, cleanUri);
    }
}