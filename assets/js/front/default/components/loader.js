const offset = (el) => {
    const rect = el.getBoundingClientRect();
    return {
        left: rect.left + window.scrollX,
        top: rect.top + window.scrollY,
        bottom: rect.bottom + window.scrollY,
    };
}

/**
 * To hide loader
 */
export function hideLoader(parent) {
    if (parent) {
        parent.querySelectorAll('.loader').forEach(loader => {
            if (loader && !loader.classList.contains('d-none')) {
                loader.classList.add('d-none');
            }
        });
    }
}

/**
 * To display loader
 */
export function displayLoader(parent, initialPos = true) {
    if (parent) {
        let loader = parent.querySelector('.loader')
        if (loader) {
            loader.classList.remove('d-none')
            let inner = loader.querySelector('.inner')
            if (!initialPos && inner) {
                let loaderTop = offset(loader).top
                let windowHeight = window.innerHeight
                let topInner = Math.round((windowHeight - loaderTop) / 2)
                let scrollTopLimit = loaderTop
                let scrollBottomLimit = loader.clientHeight - loaderTop
                loader.classList.add('position')
                if (!inner.classList.contains('position-absolute')) {
                    inner.classList.add('position-absolute')
                }
                if (topInner > 0) {
                    inner.style.top = topInner + 'px'
                }
                window.addEventListener("scroll", function (event) {
                    let top = loaderTop + window.scrollY
                    if (top > scrollTopLimit && top < scrollBottomLimit) {
                        inner.style.top = top + 'px'
                    } else if (top < scrollTopLimit) {
                        inner.style.top = topInner + 'px'
                    }
                })
            }
        }
    }
}