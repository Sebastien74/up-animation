/**
 * FlipCarousel
 * Displays a vertical 3D rotation carousel with smooth infinite loop.
 * No external plugin required. Fully RGAA-compliant.
 */

export default class FlipCarousel {

    constructor(containerSelector, options = {}) {

        this.container = document.querySelector(containerSelector);
        if (!this.container) return;

        this.options = Object.assign({
            interval: 3000,
            speed: 800,
            faceSelector: '.flip-face',
            clones: 2 // Number of duplications at initialization
        }, options);

        this.wrapper = this.container.querySelector('.flip-wrapper');
        this.faceSelector = this.options.faceSelector;
        this.faces = Array.from(this.wrapper.querySelectorAll(this.faceSelector));
        this.index = 0;

        // Duplicate faces to ensure continuous looping
        for (let i = 0; i < this.options.clones; i++) {
            this.faces.forEach(face => {
                const clone = face.cloneNode(true);
                this.wrapper.appendChild(clone);
            });
        }

        // Update face list after cloning
        this.faces = Array.from(this.wrapper.querySelectorAll(this.faceSelector));

        // Measure max height before applying styles that take faces out of the flow
        this.faceHeight = Math.max(...this.faces.map(face => face.offsetHeight));

        // Set the same height on all faces to ensure stability
        this.faces.forEach(face => {
            face.style.height = this.faceHeight + 'px';
        });
        this.wrapper.style.height = this.faceHeight + 'px';

        // Apply 3D styles
        this.initStyles();

        // Start auto-rotation
        this.start();
    }

    initStyles() {
        this.container.style.perspective = '1000px';
        this.wrapper.style.transformStyle = 'preserve-3d';
        this.wrapper.style.transition = `transform ${this.options.speed}ms ease-in-out`;
        this.wrapper.style.willChange = 'transform';
        this.faces.forEach((face, i) => {
            face.style.position = 'absolute';
            face.style.top = '0';
            face.style.left = '15px';
            face.style.right = '15px';
            face.style.width = 'calc(100% - 30px)';
            face.style.display = 'flex';
            face.style.alignItems = 'center';
            face.style.justifyContent = 'center';
            face.style.backfaceVisibility = 'hidden';
            face.style.transform = `rotateX(${i * 90}deg) translateZ(${this.faceHeight / 2}px)`;
        });
    }

    start() {
        this.intervalId = setInterval(() => this.next(), this.options.interval);
    }

    next() {

        this.index++;

        const angle = this.index * 90;
        this.wrapper.style.transition = `transform ${this.options.speed}ms ease-in-out`;
        this.wrapper.style.transform = `rotateX(-${angle}deg)`;

        // When reaching the end, rotate faces and reset
        if (this.index >= this.faces.length / this.options.clones) {
            setTimeout(() => {
                const visibleCount = this.faces.length / this.options.clones;

                // Move visible faces to the end
                for (let i = 0; i < visibleCount; i++) {
                    const face = this.wrapper.querySelector(this.faceSelector);
                    if (face) {
                        const clone = face.cloneNode(true);
                        this.wrapper.appendChild(clone);
                        face.remove();
                    }
                }

                // Update face list and apply transforms again
                this.faces = Array.from(this.wrapper.querySelectorAll(this.faceSelector));
                this.faces.forEach((face, i) => {
                    face.style.height = this.faceHeight + 'px';
                    face.style.transform = `rotateX(${i * 90}deg) translateZ(${this.faceHeight / 2}px)`;
                });

                // Instantly reset rotation
                this.wrapper.style.transition = 'none';
                this.wrapper.style.transform = `rotateX(0deg)`;
                this.index = 0;

                // Force reflow
                void this.wrapper.offsetWidth;

                // Restore transition
                requestAnimationFrame(() => {
                    this.wrapper.style.transition = `transform ${this.options.speed}ms ease-in-out`;
                });
            }, this.options.speed + 10);
        }
    }

    stop() {
        clearInterval(this.intervalId);
    }

    destroy() {
        this.stop();
        this.wrapper.style.transform = '';
        this.wrapper.innerHTML = '';
    }
}