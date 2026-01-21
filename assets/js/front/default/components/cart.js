import Cookies from 'js-cookie'

let bubbleInfo = document.getElementById('cart-bubble-info')
let cookiesName = 'cart_list'
let cookiesCart = Cookies.get(cookiesName)
let cookiesInit = typeof cookiesCart != 'undefined' && cookiesCart !== '[object Object]'
let cookiesCartList = cookiesInit ? cleanList(JSON.parse(cookiesCart)) : []

/** Add to cart buttons event */
let addToCart = document.querySelectorAll('.add-to-cart')
for (let i = 0; i < addToCart.length; i++) {
    let el = addToCart[i]
    el.onclick = function (e) {
        e.preventDefault()
        let inCart = el.dataset.cart === 'on'
        let newCartStatus = inCart ? 'off' : 'on'
        let oldCartStatus = newCartStatus === 'off' ? 'on' : 'off'
        let addToCartEls = document.querySelectorAll('.add-to-cart')
        for (let j = 0; j < addToCartEls.length; j++) {
            let btn = addToCartEls[j]
            if (btn.dataset.id === el.dataset.id) {
                if (newCartStatus === 'on') {
                    btn.querySelectorAll('.icon-on')[0].classList.remove('d-none')
                    btn.querySelectorAll('.icon-off')[0].classList.add('d-none')
                } else {
                    btn.querySelectorAll('.icon-on')[0].classList.add('d-none')
                    btn.querySelectorAll('.icon-off')[0].classList.remove('d-none')
                    cookiesCartList = cleanList(cookiesCartList, btn.dataset.id)
                }
                btn.setAttribute('data-cart', newCartStatus)
                btn.classList.replace(oldCartStatus, newCartStatus)
            }
        }
        setCookies(cookiesCartList)
    }
}

/** To set cookies Products list */
function setCookies(cookiesCartList) {

    let secure = location.protocol !== "http:"
    let productsInCart = document.querySelectorAll('.add-to-cart')

    for (let i = 0; i < productsInCart.length; i++) {
        let product = productsInCart[i]
        let productId = product.dataset.id
        let inCart = product.dataset.cart
        if (!checkId(productId, cookiesCartList) && inCart === 'on') {
            cookiesCartList.push({id: parseInt(productId), quantity: parseInt("1")})
        }
    }

    cookiesCartList = cleanList(cookiesCartList)

    Cookies.set(cookiesName, JSON.stringify(cookiesCartList), {
        expires: 1,
        path: '/',
        domain: document.domain,
        secure: secure
    })

    bubbleInfo.innerHTML = String(cookiesCartList.length)
    if (cookiesCartList.length > 0) {
        bubbleInfo.classList.remove('d-none')
    } else {
        bubbleInfo.classList.add('d-none')
    }
}

/** To check if ID already existing in Cookies list */
function checkId(productId, cookiesCartList) {
    cookiesCartList.forEach((product) => {
        if (product && parseInt(product.id) === parseInt(productId)) {
            return true;
        }
    })
    return false;
}

/** To clean Cookies list */
function cleanList(cookiesCartList, productID = null) {

    let ids = []
    let cookies = []
    cookiesCartList.forEach((product) => {
        if (product && product.id && !ids.includes(parseInt(product.id)) && parseInt(product.id) !== parseInt(productID)) {
            cookies.push(product)
        }
        if (product && product.id) {
            ids.push(parseInt(product.id))
        }
    })

    return cookies
}