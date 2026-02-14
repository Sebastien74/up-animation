export function Counter(Splide, Components) {

    const {track} = Components.Elements;
    const slider = track.closest('.splide');

    let elm;

    function mount() {
        elm = document.createElement('div');
        elm.classList.add('item-counter');
        elm.style.textAlign = 'center';
        elm.style.marginTop = '0.5em';
        const counter = slider.querySelector('.item-counter');
        if (counter) {
            counter.remove();
        }
        track.parentElement.insertBefore(elm, track.nextSibling);
        update();
        Splide.on('move', update);
    }

    function update() {
        elm.innerHTML = '<span class="current-count">' + (Splide.index + 1) + '</span>/' + Splide.length;
    }

    return {
        mount,
    }
}