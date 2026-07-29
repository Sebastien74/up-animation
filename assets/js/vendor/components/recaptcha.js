/**
 * Self-hosted proof-of-work captcha solver (no third party).
 *
 * Fetches a signed challenge, brute-forces the SHA-256 proof in a Web Worker,
 * and stores the ALTCHA-compatible payload on the hidden field. Keeps the
 * historical generate()/onSubmit() API so every form keeps working.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
import {sha256hex} from './sha256hex';

function solveInWorker(salt, target, max) {
    return new Promise((resolve) => {
        // Real webpack-emitted worker (no inline `blob:`): webpack wraps the URL
        // with its Trusted Types policy, satisfying `require-trusted-types-for 'script'`.
        const worker = new Worker(new URL('./captcha-worker.js', import.meta.url));
        worker.onmessage = (e) => { resolve(e.data); worker.terminate(); };
        worker.postMessage({salt, target, max});
    });
}

function solveOnMainThread(salt, target, max) {
    for (let n = 0; n <= max; n++) {
        if (sha256hex(salt + n) === target) return n;
    }
    return -1;
}

async function solve(challenge) {
    const {algorithm, challenge: target, salt, signature, maxnumber} = challenge;
    const number = typeof Worker !== 'undefined'
        ? await solveInWorker(salt, target, maxnumber)
        : solveOnMainThread(salt, target, maxnumber);

    if (number < 0) return null;

    return btoa(JSON.stringify({algorithm, challenge: target, number, salt, signature}));
}

function prepare(dataEl) {
    const form = dataEl.closest('form');
    const field = form ? form.querySelector('.field_ho') : null;
    if (!form || !field) return;

    fetch(dataEl.dataset.challenge, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then((response) => (response.ok ? response.json() : null))
        .then((challenge) => (challenge ? solve(challenge) : null))
        .then((payload) => { if (payload) { field.dataset.solution = payload; field.value = payload; } })
        .catch(() => {});
}

export function generate() {
    document.querySelectorAll('form .form-data[data-challenge]').forEach(prepare);
}

export function onSubmit(form) {
    const field = form.querySelector('.field_ho');
    if (!field) return;

    field.type = 'hidden';
    if (!field.value && field.dataset.solution) {
        field.value = field.dataset.solution;
    }
}
