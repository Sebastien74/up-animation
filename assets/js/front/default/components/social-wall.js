/**
 * Social wall
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let isDebug = parseInt(document.documentElement.dataset.debug);

    function isElementInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    function addScript(socialWall) {
        isDebug = typeof socialWall.dataset.debug !== 'undefined' ? parseInt(socialWall.dataset.debug) : isDebug;
        const allowed = isDebug === 0 || isDebug === 1 && parseInt(socialWall.dataset.debug) === 1;
        if (allowed && !socialWall.classList.contains('loaded')) {
            let head = document.head
            /** Create script elem */
            let scriptHead = document.createElement("script")
            scriptHead.type = "text/javascript"
            scriptHead.src = socialWall.dataset.src
            /** Inject */
            let widgetBlock = socialWall.closest('.widget-block')
            head.append(scriptHead)
            if (widgetBlock) {
                widgetBlock.innerHTML = socialWall.dataset.element
            } else {
                socialWall.innerHTML = socialWall.dataset.element
            }
            socialWall.classList.add('loaded')
        }
    }

    document.querySelectorAll('.social-wall-wrap').forEach(socialWall => {
        let currentCol = socialWall.closest('.layout-col') ? socialWall.closest('.layout-col') : socialWall.parentNode;
        let previousCol = currentCol ? currentCol.previousElementSibling : null;
        let currentZone = currentCol && currentCol.closest('.layout-zone') ? currentCol.closest('.layout-zone') : (currentCol ? currentCol.parentNode : null);
        let previousZone = currentZone ? currentZone.previousElementSibling : null;
        let zone = previousZone ? previousZone : currentZone;
        let detectElement = previousCol ? previousCol : zone;
        if (isElementInViewport(detectElement) || isElementInViewport(socialWall)) {
            addScript(socialWall)
        }
    });

    window.addEventListener('scroll', () => {
        document.querySelectorAll('.social-wall-wrap').forEach(socialWall => {
            if (isElementInViewport(socialWall)) {
                addScript(socialWall)
            }
        });
    })
}