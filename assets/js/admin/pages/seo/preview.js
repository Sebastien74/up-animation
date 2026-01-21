/**
 * Preview
 */
export default function () {

    let isEmpty = function (str) {
        return typeof str === 'string' && !str.trim() || typeof str === 'undefined' || str === null;
    }

    let overview = document.querySelector('#google-preview');
    let prism = document.querySelector('#highlight-preview');
    let form = document.querySelector('body form[name="seo"]');
    let canonicalPattern = overview.getAttribute('data-canonical-pattern');

    let title = form.querySelector(".meta-title").value.trim();
    if (isEmpty(title)) {
        title = overview.getAttribute('data-title');
    }

    let titleAfterDash = '';
    let afterDashActive = overview.getAttribute('data-dash-active');
    let titleAfterDashOverview = overview.getAttribute('data-dash');

    if (afterDashActive) {
        titleAfterDash = form.querySelector(".meta-title-second").value.trim();
        if (titleAfterDash || titleAfterDashOverview) {
            if (isEmpty(titleAfterDash)) {
                titleAfterDash = " - " + titleAfterDashOverview;
            } else {
                titleAfterDash = " - " + titleAfterDash;
            }
        }
    }

    let canonical = form.querySelector(".meta-canonical").value.trim();
    if (isEmpty(canonical)) {
        canonical = overview.getAttribute('data-canonical');
    } else {
        canonical = canonicalPattern + canonical;
    }

    let description = form.querySelector(".meta-description").value.trim();
    if (isEmpty(description)) {
        description = overview.getAttribute('data-description');
    }

    let ogTitle = form.querySelector(".meta-og-title").value.trim();
    if (isEmpty(ogTitle)) {
        ogTitle = overview.getAttribute('data-og-title');
    }

    let ogDescription = form.querySelector(".meta-og-description").value.trim();
    if (isEmpty(ogDescription)) {
        ogDescription = overview.getAttribute('data-og-description');
    }

    overview.querySelector(".seo-title span.title").innerHTML = title;
    overview.querySelector(".seo-title span.title-dash").innerHTML = titleAfterDash;
    overview.querySelector(".seo-canonical").innerHTML = canonical;
    overview.querySelector(".seo-description").innerHTML = description;

    prism.querySelector('.highlight-title').innerHTML = '&lt;title>' + title + titleAfterDash + '&lt;/title>';
    prism.querySelector('.highlight-description').innerHTML = '&lt;meta name="description" content="' + description + '" />';
    prism.querySelector('.highlight-og-title').innerHTML = '&lt;meta property="og:title" content="' + ogTitle + '" />';
    prism.querySelector('.highlight-og-description').innerHTML = '&lt;meta property="og:description" content="' + ogDescription + '" />';
    prism.querySelector('.highlight-canonical').innerHTML = '&lt;link rel="canonical" href="' + canonical + '" />';
    prism.querySelector('.highlight-og-url').innerHTML = '&lt;meta property="og:url" content="' + canonical + '" />';

    Prism.highlightElement(document.querySelector('.highlight-title'));
    Prism.highlightElement(document.querySelector('.highlight-description'));
    Prism.highlightElement(document.querySelector('.highlight-canonical'));
    Prism.highlightElement(document.querySelector('.highlight-og-title'));
    Prism.highlightElement(document.querySelector('.highlight-og-description'));
    Prism.highlightElement(document.querySelector('.highlight-og-url'));
}