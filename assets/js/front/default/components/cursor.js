import MouseFollower from 'mouse-follower';
import { gsap } from 'gsap';

export default class Cursor {
    constructor() {
        if (!window.matchMedia('(min-width: 992px)').matches || window.matchMedia('(pointer: coarse)').matches) {
            return;
        }

        MouseFollower.registerGSAP(gsap);

        this.gsap = gsap;
        this.cursor = new MouseFollower({
            container: document.body,
            speed: 0.3,
            ease: 'expo.out',
            magnetic: true,
            stateDetection: {
                '-large': '.enlarge-cursor, .hide-cursor',
                '-btn': '.btn',
                '-link': 'a',
                '-magnetic': '.is-magnetic',
                '-card-arrow': '.card .card-header a',
            },
            visible: true,
            visibleOnState: false,
            skewing: 2,
            skewingText: 3,
            skewingIcon: 2,
            skewingMedia: 2,
            skewingDelta: 0.002,
            skewingDeltaMax: 0.1,
            stickDelta: 0.05,
            showTimeout: 10,
            hideOnLeave: true,
            hideTimeout: 200,
            hideMediaTimeout: 200,
        });

        window.mouseCursor = this.cursor;

        this.bindMagnetic();
    }

    bindMagnetic() {
        document.querySelectorAll('.is-magnetic').forEach((el) => {
            el.addEventListener('mouseenter', () => {
                this.cursor.setSkewing(0);
                this.gsap.to(el, { scale: 1.05, duration: 0.3 });
            });
            el.addEventListener('mousemove', (e) => {
                const rect = el.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                this.gsap.to(el, { x: 0.15 * x, y: 0.15 * y, duration: 0.3 });
            });
            el.addEventListener('mouseleave', () => {
                this.cursor.removeSkewing();
                this.gsap.to(el, { x: 0, y: 0, scale: 1, duration: 0.3 });
            });
        });
    }
}
