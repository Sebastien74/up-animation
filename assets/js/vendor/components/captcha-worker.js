/**
 * Proof-of-work Web Worker.
 *
 * Emitted as a real same-origin asset by webpack (`new Worker(new URL(...))`),
 * so no inline `blob:` script is generated. webpack wraps the worker URL with
 * its Trusted Types policy, satisfying `require-trusted-types-for 'script'`.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
import {sha256hex} from './sha256hex';

self.onmessage = function (e) {
    const {salt, target, max} = e.data;
    for (let n = 0; n <= max; n++) {
        if (sha256hex(salt + n) === target) {
            self.postMessage(n);
            return;
        }
    }
    self.postMessage(-1);
};
