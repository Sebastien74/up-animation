import '../../../../scss/front/default/components/_audio.scss'
import {Player} from 'shikwasa'

/**
 * Audio
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    /** Example :
     *
     *  <div class="my-hover-element">
     *       <div class="audio-hover" data-src="path-to-mp3-file" data-target=".my-hover-element"></div>
     *  </div>
     */
        //
        // audiosHover.each(function () {
        //
        //     let xhr = new XMLHttpRequest();
        //     let audio = $(this);
        //
        //     xhr.open('GET', audio.data('src'));
        //     xhr.responseType = 'arraybuffer';
        //     xhr.addEventListener('load', () => {
        //         let audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        //         let playSound = (audioBuffer) => {
        //             let target = audio.data('target') ? $(this).closest(audio.data('target')) : audio.parent();
        //             target.mouseenter(function () {
        //                 let source = audioCtx.createBufferSource();
        //                 source.buffer = audioBuffer;
        //                 source.connect(audioCtx.destination);
        //                 source.loop = false;
        //                 source.start();
        //                 target.mouseleave(function () {
        //                     source.stop();
        //                 });
        //             });
        //         };
        //         audioCtx.decodeAudioData(xhr.response).then(playSound);
        //     });
        //     xhr.send();
        // });

    let players = document.querySelectorAll('[data-component="audio-player"]')
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