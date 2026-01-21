let scriptEl = document.getElementById('google-analytics-src');
if (scriptEl) {
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', scriptEl.dataset.ua);
}