const scriptEl = document.getElementById('google-analytics-src');
if (scriptEl && !scriptEl.classList.contains('script-loaded')) {
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', scriptEl.dataset.ua);
    scriptEl.classList.add('script-loaded');
}