/**
 * To set img size attributes
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

let body = document.body;

let setAttributes = function () {

    let images = body.querySelectorAll('img')

    let setImage = function (width, height, image) {
        if (width > 1 && height > 1 && width && !image.classList.contains('force-size')) {
            let attributeWidth = parseInt(image.getAttribute('width'))
            let attributeHeight = parseInt(image.getAttribute('height'))
            let asLazy = image.classList.contains('force-size') || image.classList.contains('lazy-load')
            if (attributeWidth !== width) {
                if (!asLazy) {
                    image.setAttribute('width', width.toString())
                }
            }
            if (attributeHeight !== height) {
                if (!asLazy) {
                    image.setAttribute('height', height.toString())
                }
            }
        }
    }

    for (let i = 0; i < images.length; i++) {
        let image = images[i]
        let width = image.offsetWidth
        let height = image.offsetHeight
        setImage(width, height, image)
    }
}

if (!body.classList.contains('skin-admin')) {
    setAttributes()
}