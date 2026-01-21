/**
 * Lazy loading screen images
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (images) {

    let setImages = function (images) {

        let height = window.innerHeight;
        let width = window.innerWidth;
        let orientation = height > width ? 'portrait' : 'landscape';
        let screenType = width > 991 ? 'desktop' : 'tablet';
        if (width < 768) {
            screenType = 'mobile';
        }

        for (let i = 0; i < images.length; i++) {

            let el = images[i];
            let src = el.dataset.src
            let desktopSrc = el.dataset.desktop
            let tabletSrc = el.dataset.tablet
            let mobileSrc = el.dataset.mobile

            if (orientation === 'portrait') {
                if (screenType === 'mobile' && typeof mobileSrc != 'undefined') {
                    src = mobileSrc
                } else if (screenType === 'mobile' && typeof mobileSrc == 'undefined' && typeof tabletSrc != 'undefined') {
                    src = tabletSrc
                } else if (screenType === 'tablet' && typeof tabletSrc != 'undefined') {
                    src = tabletSrc
                } else if (screenType === 'tablet' && typeof tabletSrc == 'undefined' && typeof mobileSrc != 'undefined') {
                    src = mobileSrc
                }
            }

            src = orientation === 'landscape' && typeof desktopSrc != 'undefined'
                ? desktopSrc : src

            let webp = el.parentNode.getElementsByClassName('img-webp')
            if (webp.length > 0 && src) {
                webp[0].setAttribute('src', src)
                webp[0].setAttribute('srcset', src)
            } else if (src) {
                el.setAttribute('src', src)
            }
        }
    };

    if (!document.body.classList.contains('skin-admin')) {
        setImages(images)
        window.addEventListener("resize", function (event) {
            setImages(images)
        })
    }
}