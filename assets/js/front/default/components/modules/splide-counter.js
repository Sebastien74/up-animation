export function Counter(Splide, Components) {

    const {track} = Components.Elements

    let elm

    function mount() {
        elm = document.createElement('div')
        elm.classList.add('itemcounter')
        elm.style.textAlign = 'center'
        elm.style.marginTop = '0.5em'

        track.parentElement.insertBefore(elm, track.nextSibling)

        update()
        Splide.on('move', update)
    }

    function update() {
        elm.innerHTML = '<span class="currentcount">' + (Splide.index + 1) + '</span>/' + Splide.length
    }

    return {
        mount,
    }
}