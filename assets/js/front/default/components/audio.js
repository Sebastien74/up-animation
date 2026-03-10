import {Player} from 'shikwasa'

/**
 * Audio
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let players = document.querySelectorAll('[data-component="audio-player"]')
    if (players.length > 0) {
        import('../../../../scss/front/default/components/_audio.scss');
    }
    for (let i = 0; i < players.length; i++) {
        const el = players[i]
        if (!el.classList.contains('loaded')) {
            const title = el.dataset.title !== 'false' ? el.dataset.title : null
            const artist = el.dataset.artist !== 'false' ? el.dataset.artist : null
            const cover = el.dataset.cover !== 'false' ? el.dataset.cover : null
            if (!title && !artist) {
                el.classList.add('hide-shk-text')
            }
            const player = new Player({
                container: () => document.getElementById(el.getAttribute('id')),
                audio: {
                    title: title,
                    artist: artist,
                    cover: cover,
                    src: el.dataset.src,
                },
                theme: {
                    type: el.dataset.theme ? el.dataset.theme : 'auto', /** 'auto' | 'dark' | 'light' */
                },
            })
            el.classList.add('loaded')
        }
    }
}