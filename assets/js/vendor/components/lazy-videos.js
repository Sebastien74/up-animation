/**
 * Lazy loading video
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

let allYouTubePlayers = [];

export function lazyVideos(videosYoutube = [], videos = []) {

    /** Controls */
    let controls = function (video, videoContainer) {

        let videoBlock = video.closest('.video-block-html');

        /** Player controls */
        videoBlock.querySelectorAll('.player-control').forEach(function (playerControl) {
            let parent = playerControl.closest('.video-block-html');
            if (parent && playerControl.classList.contains('control-play-btn')) {
                playerControl.addEventListener("mouseenter", function () {
                    parent.classList.add('hover-player')
                });
                playerControl.addEventListener("mouseleave", function () {
                    parent.classList.remove('hover-player')
                });
            }
            playerControl.onclick = function (event) {
                event.preventDefault();
                if (video.paused) {
                    video.play();
                } else {
                    video.pause();
                }
                viewHtml(video, videoContainer);
            }
        });

        video.addEventListener("pause", function () {
            viewHtml(video, videoContainer);
        });

        /** Sound control */
        let soundControl = videoBlock.querySelector('.sound-control');
        if (soundControl) {
            soundControl.onclick = function (event) {
                event.preventDefault();
                video.muted = !video.muted;
                let text = !soundControl.classList.contains('active') ? soundControl.dataset.pauseText : soundControl.dataset.playText;
                soundControl.setAttribute('title', text);
                let tooltipId = soundControl.getAttribute('aria-describedBy');
                let tooltip = tooltipId ? document.getElementById(tooltipId) : null;
                if (tooltip) {
                    tooltip.remove();
                }
                soundControl.querySelector('.pause').classList.toggle('d-none');
                soundControl.querySelector('.play').classList.toggle('d-none');
                soundControl.classList.toggle('active')
            }
        }
    }

    /** Play On Hover */
    let playOnHover = function (video, videoContainer) {
        if (videoContainer && video.classList.contains('play-in-hover')) {
            video.addEventListener('loadeddata', () => {
                videoContainer.addEventListener('mouseenter', e => {
                    if (video.paused) {
                        video.play();
                        viewHtml(video, videoContainer);
                    }
                })
            })
        }
    }

    /** Autoplay */
    let autoplayVideo = function (video, videoContainer) {
        if (video.classList.contains('autoplay')) {
            video.addEventListener('loadeddata', () => {
                if (video.paused) {
                    video.play();
                    viewHtml(video, videoContainer);
                }
            })
        }
    }

    let init = function () {

        let YouTubeContainers = videosYoutube ? videosYoutube : document.querySelectorAll(".embed-youtube");
        YouTubeContainers.forEach(YouTubeContainer => {
            playYoutube(YouTubeContainer, 'click');
        });

        let lazyVideos = videos ? videos : [].slice.call(document.querySelectorAll("video.lazy-video"))

        if ("IntersectionObserver" in window) {

            let lazyVideoObserver = new IntersectionObserver(function (entries, observer) {
                entries.forEach(function (video) {
                    let target = video.target;
                    let sizes = getSizes(target);
                    if (video.isIntersecting) {
                        if (sizes.height > 0) {
                            target.style.maxHeight = sizes.height + 'px';
                        }
                        target.style.objectFit = 'cover';
                        for (let source in video.target.children) {
                            let videoSource = video.target.children[source];
                            if (typeof videoSource.tagName === "string" && videoSource.tagName === "SOURCE") {
                                let dataSrc = videoSource.dataset.src;
                                if (dataSrc) {
                                    videoSource.src = dataSrc;
                                }
                            }
                        }
                        // Load subtitle tracks
                        let track = video.target.querySelector('track[data-src]');
                        if (track && track.dataset.src) {
                            track.src = track.dataset.src;
                            track.removeAttribute('data-src');
                            video.target.textTracks[0].mode = 'showing';
                        }
                        video.target.load();
                        video.target.classList.remove("lazy");
                        lazyVideoObserver.unobserve(video.target);
                    }
                });
            });

            if (lazyVideos.length > 0) {
                lazyVideos.forEach(function (lazyVideo) {
                    lazyVideoObserver.observe(lazyVideo);
                    let videoBlockCarousel = lazyVideo.closest('.carousel-item');
                    let videoContainer = videoBlockCarousel ? videoBlockCarousel : lazyVideo.parentNode;
                    controls(lazyVideo, videoContainer);
                    autoplayVideo(lazyVideo, videoContainer);
                    playOnHover(lazyVideo, videoContainer);
                });
            }
        }
    }

    init();
}

export function viewHtml(video, videoContainer) {

    let videoBlock = video.closest('.video-block-html');
    let playing = !video.paused;

    /** Elements to hide on play */
    let elsToHide = video.dataset.elementsToHide;
    if (elsToHide) {
        elsToHide = elsToHide.split(',');
        for (let i = 0; i < elsToHide.length; i++) {
            let elToHide = document.querySelector(elsToHide[i]);
            if (elToHide && playing) {
                elToHide.classList.add('d-none');
            } else if (elToHide) {
                elToHide.classList.remove('d-none');
            }
        }
    }

    /** Playing classes */
    if (playing && !video.classList.contains('playing')) {
        videoBlock.classList.add('playing');
        video.classList.add('playing');
    } else if (!playing && video.classList.contains('playing')) {
        videoBlock.classList.remove('playing');
        video.classList.remove('playing');
    }

    /** Overlay */
    let overlayEl = videoBlock.querySelector('.overlay-video');
    if (overlayEl && playing) {
        overlayEl.classList.add('hide-overlay');
    } else if (overlayEl) {
        overlayEl.classList.remove('hide-overlay');
    }

    /** Controls */
    let playBtn = videoBlock.querySelector('.control-play');
    let pauseBtn = videoBlock.querySelector('.control-pause');

    if (playBtn && playing) {
        if (!playBtn.classList.contains('d-none')) {
            playBtn.classList.add('d-none');
        }
        if (pauseBtn) {
            pauseBtn.classList.remove('d-none');
        }
    }

    if (pauseBtn && !playing) {
        if (!pauseBtn.classList.contains('d-none')) {
            pauseBtn.classList.add('d-none');
        }
        if (playBtn) {
            playBtn.classList.remove('d-none');
        }
    }
}

export function playHtml(player, type) {

    if (type === 'autoplay' && player.tagName === 'VIDEO') {

        player.setAttribute('muted', '');
        player.setAttribute('loop', '');
        player.setAttribute('playsinline', '');

        let observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    let video = entry.target;
                    if (video.paused && !video.classList.contains('in-progress')) {
                        stopVideos();
                        let sizes = getSizes(video);
                        if (sizes.height > 0) {
                            video.style.maxHeight = sizes.height + 'px';
                        }
                        video.style.objectFit = 'cover';
                        for (let source in video.children) {
                            let videoSource = video.children[source];
                            if (typeof videoSource.tagName === "string" && videoSource.tagName === "SOURCE") {
                                let dataSrc = videoSource.dataset.src;
                                if (dataSrc) {
                                    videoSource.src = dataSrc;
                                }
                            }
                        }
                        video.load();
                        video.classList.add('playing');
                        video.muted = true; // Mute the video
                        video.addEventListener('loadeddata', function () {
                            if (!video.classList.contains('in-progress')) {
                                // Play the video
                                video.play().then(() => {
                                    video.classList.add('in-progress');
                                    console.log('Video playback started successfully');
                                }).catch(error => {
                                    console.error('Video playback failed:', error);
                                });
                            }
                        }, {once: true}); // The 'once' option auto-removes the listener
                    }
                }
            });
        }, {threshold: 0.1}); // You can adjust the threshold as needed

        // Observe the video
        observer.observe(player);
    }
}

export function playYoutube(playerYoutube, type) {

    try {

        if (!playerYoutube) {
            return;
        }

        let sizes = getSizes(playerYoutube);

        // Avoid regenerating IDs each time playYoutube() is called
        let playersYoutube = document.querySelectorAll('.embed-data');
        playersYoutube.forEach(player => {
            if (player.dataset.randomId) {
                return;
            }
            const randomUUID = 'loaded-' + window.crypto.randomUUID();
            player.setAttribute('id', randomUUID);
            player.dataset.randomId = randomUUID;

            const loader = player.querySelector('.iframe-loader');
            if (loader) {
                loader.setAttribute('id', 'iframe-' + randomUUID);
            }
        });

        function loadYouTubePoster(playerYoutube, sizes) {

            let playerData = playerYoutube.closest('.embed-data');
            let existingPoster = playerData.querySelector('.poster');

            // Check if a poster already exists
            if (existingPoster) {
                // Make the existing poster visible again
                existingPoster.style.display = '';
            } else {
                // No existing poster, load a new one
                const getMeta = async (url) => {
                    const img = new Image();
                    img.src = url;
                    await img.decode();
                    return img;
                };

                /** Load the Thumbnail Image asynchronously: sddefault / mqdefault / hqdefault */
                let imageAlt = playerData.dataset.title ? playerData.dataset.title : "poster";
                let poster = playerData.dataset.poster;
                let imageSource = poster ? poster : "https://img.youtube.com/vi/" + playerData.dataset.videoId + "/sddefault.jpg";

                getMeta(imageSource).then(img => {
                    if (!poster && parseInt(img.naturalWidth) < 400) {
                        imageSource = "https://img.youtube.com/vi/" + playerData.dataset.videoId + "/hqdefault.jpg";
                    }
                    let image = new Image();
                    image.src = imageSource;
                    image.alt = imageAlt;
                    image.id = 'poster-' + playerData.getAttribute('id');
                    if (sizes.height > 0) {
                        image.style.maxHeight = sizes.height + 'px';
                    }
                    image.style.objectFit = 'cover';
                    image.classList.add('poster', 'img-fluid');
                    image.addEventListener("load", function () {
                        playerData.appendChild(image);
                    });
                });

                if (sizes.height > 0) {
                    playerData.parentNode.style.maxHeight = sizes.height + 'px';
                }
            }
        }

        loadYouTubePoster(playerYoutube, sizes);

        function loadYouTubeAPI() {
            // Avoid injecting script multiple times
            if (document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
                return;
            }
            let tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            let firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }

        function ensureYouTubeAPIReady(callback) {
            if (window.YT && window.YT.Player) {
                callback();
            } else {
                loadYouTubeAPI();
                let checkInterval = setInterval(function () {
                    if (window.YT && window.YT.Player) {
                        clearInterval(checkInterval);
                        callback();
                    }
                }, 100);
            }
        }

        function setPlayer(playerYoutube) {
            stopVideos();
            playerYoutube.classList.add('in-progress');

            const autoplay = playerYoutube.dataset.autoplay && parseInt(playerYoutube.dataset.autoplay) === 1 ? 1 : 0;
            let newPlayer = document.getElementById(playerYoutube.getAttribute('id'));
            let iframeWrap = newPlayer.querySelector('.iframe-loader');
            let startSeconds = newPlayer.dataset.start ? parseInt(newPlayer.dataset.start, 10) || 0 : 0;

            let player = new YT.Player(iframeWrap.getAttribute('id'), {
                host: 'https://www.youtube-nocookie.com',
                videoId: newPlayer.dataset.videoId,
                height: sizes.height > 0 ? sizes.height : 360,
                width: sizes.width > 0 ? sizes.width : 640,
                playerVars: {
                    start: startSeconds,
                    rel: 0,
                    autoplay: autoplay,
                    mute: autoplay ? 1 : 0
                },
                events: {
                    'onReady': onPlayerReady,
                    'onStateChange': onPlayerStateChange
                }
            });

            iframeWrap = document.getElementById(iframeWrap.getAttribute('id'));

            let parent = iframeWrap.closest('.embed-data');
            parent.classList.add('ratio', 'ratio-16x9');

            let playBtn = parent.querySelector('.embed-youtube-play');
            if (playBtn) {
                playBtn.classList.remove('d-flex');
                playBtn.classList.add('d-none');
            }

            let poster = parent.querySelector('.poster');
            if (poster) {
                poster.classList.add('d-none');
            }

            let iframe = parent.querySelector('iframe');
            iframe.classList.add('embed-responsive-item');
            iframe.classList.remove('embed-youtube');

            if (sizes.height > 0) {
                iframe.style.maxHeight = sizes.height + 'px';
            }
            iframe.style.objectFit = 'cover';
            iframe.classList.remove('d-none');

            allYouTubePlayers.push(player);

            return {
                iframe: iframe,
                player: player
            };
        }

        function onPlayerReady(event) {
            event.target.playVideo();
        }

        /**
         * IMPORTANT:
         * YouTube emits PAUSED when the user clicks the timeline/progress bar.
         * Resetting the iframe/player on PAUSED will make the browser exit fullscreen.
         * So we only reset on ENDED.
         */
        function onPlayerStateChange(event) {
            if (event.data === YT.PlayerState.ENDED) {
                onStopVideoYoutube(event.target);
            }
        }

        ensureYouTubeAPIReady(function () {
            playerYoutube = document.getElementById(playerYoutube.getAttribute('id'));

            const autoplay = playerYoutube.dataset.autoplay ? parseInt(playerYoutube.dataset.autoplay) === 1 : false;
            const loop = playerYoutube.dataset.loop ? parseInt(playerYoutube.dataset.loop) === 1 : false;

            if (type === 'click' && !autoplay) {
                // Avoid stacking multiple click listeners
                if (!playerYoutube.dataset.ytClickBound) {
                    playerYoutube.dataset.ytClickBound = '1';
                    playerYoutube.addEventListener("click", function () {
                        stopVideos();
                        setPlayer(playerYoutube);
                    });
                }
            } else if (type === 'autoplay' || autoplay) {
                if (!playerYoutube.classList.contains('in-progress')) {
                    stopVideos();
                    let config = setPlayer(playerYoutube);

                    let iframe = config.iframe;
                    let src = iframe.getAttribute('src');

                    if (src && !src.includes('autoplay') && !src.includes('mute')) {
                        let srcUrl = new URL(src);
                        srcUrl.searchParams.append('autoplay', '1');
                        srcUrl.searchParams.append('mute', '1');
                        iframe.src = srcUrl.toString();
                    }

                    if (loop && src) {
                        // Ensure loop param is applied
                        let loopUrl = new URL(iframe.getAttribute('src'));
                        loopUrl.searchParams.set('loop', '1');
                        iframe.src = loopUrl.toString();
                    }
                }
            }
        });

    } catch (error) {
        console.error(error);
    }
}

export function getSizes(player) {

    const parents = [
        {identifier: '.splide', find: '.splide__slide picture'},
        {identifier: '.carousel', find: '.carousel-item picture'},
    ];

    let width = null;
    let height = null;
    let parent = null;

    parents.forEach(config => {
        parent = player.closest(config.identifier);
        if (parent) {
            const items = parent.querySelectorAll(config.find);
            items.forEach(function (img) {
                const currentHeight = img.clientHeight;
                if (!height || currentHeight < height) {
                    width = img.clientWidth;
                    height = currentHeight;
                }
            });
            return false;
        }
    });

    return {
        parent: parent,
        width: width ? width : 0,
        height: height ? height : 0,
    };
}

export function stopVideos() {

    // Do not reset players while the document is in fullscreen.
    // Touching iframes/DOM during fullscreen may force exit fullscreen.
    if (document.fullscreenElement) {
        return;
    }

    let videos = document.querySelectorAll('video');
    videos.forEach(video => {
        video.classList.remove('in-progress');
        video.pause();
    });

    allYouTubePlayers.forEach(function (player) {
        if (player && typeof player.stopVideo === 'function') {
            player.stopVideo();
            onStopVideoYoutube(player);
        }
    });
}

export function onStopVideoYoutube(player) {
    if (player) {
        let iframe = player.getIframe();
        let playerYoutube = iframe.closest('.embed-youtube');
        let iframeWrap = iframe.closest('.iframe-wrap');
        let parent = iframe.closest('.embed-data');

        if (!playerYoutube || !parent) {
            return;
        }

        let playBtn = playerYoutube.querySelector('.embed-youtube-play');

        playerYoutube.classList.remove('in-progress');

        let poster = parent.querySelector('.poster');
        if (poster) {
            poster.classList.remove('d-none');
        }

        iframe.classList.add('d-none');
        iframe.classList.remove('in-progress');

        if (playBtn) {
            playBtn.classList.remove('d-none');
            playBtn.classList.add('d-flex');
        }

        parent.classList.add('embed-youtube');

        if (iframeWrap) {
            iframeWrap.classList.remove('ratio', 'ratio-16x9');
        }

        // Re-bind click safely (guarded by dataset.ytClickBound)
        playYoutube(parent, 'click');
    }
}
