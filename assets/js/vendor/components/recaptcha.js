/**
 * Self-hosted proof-of-work captcha solver (no third party).
 *
 * Fetches a signed challenge, brute-forces the SHA-256 proof in a Web Worker,
 * and stores the ALTCHA-compatible payload on the hidden field. Keeps the
 * historical generate()/onSubmit() API so every form keeps working.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

function sha256hex(str) {
    const rrot = (x, n) => (x >>> n) | (x << (32 - n));
    const K = [
        0x428a2f98, 0x71374491, 0xb5c0fbcf, 0xe9b5dba5, 0x3956c25b, 0x59f111f1, 0x923f82a4, 0xab1c5ed5,
        0xd807aa98, 0x12835b01, 0x243185be, 0x550c7dc3, 0x72be5d74, 0x80deb1fe, 0x9bdc06a7, 0xc19bf174,
        0xe49b69c1, 0xefbe4786, 0x0fc19dc6, 0x240ca1cc, 0x2de92c6f, 0x4a7484aa, 0x5cb0a9dc, 0x76f988da,
        0x983e5152, 0xa831c66d, 0xb00327c8, 0xbf597fc7, 0xc6e00bf3, 0xd5a79147, 0x06ca6351, 0x14292967,
        0x27b70a85, 0x2e1b2138, 0x4d2c6dfc, 0x53380d13, 0x650a7354, 0x766a0abb, 0x81c2c92e, 0x92722c85,
        0xa2bfe8a1, 0xa81a664b, 0xc24b8b70, 0xc76c51a3, 0xd192e819, 0xd6990624, 0xf40e3585, 0x106aa070,
        0x19a4c116, 0x1e376c08, 0x2748774c, 0x34b0bcb5, 0x391c0cb3, 0x4ed8aa4a, 0x5b9cca4f, 0x682e6ff3,
        0x748f82ee, 0x78a5636f, 0x84c87814, 0x8cc70208, 0x90befffa, 0xa4506ceb, 0xbef9a3f7, 0xc67178f2,
    ];
    const H = [0x6a09e667, 0xbb67ae85, 0x3c6ef372, 0xa54ff53a, 0x510e527f, 0x9b05688c, 0x1f83d9ab, 0x5be0cd19];

    const bytes = [];
    for (let i = 0; i < str.length; i++) {
        let c = str.charCodeAt(i);
        if (c < 128) bytes.push(c);
        else if (c < 2048) bytes.push(192 | (c >> 6), 128 | (c & 63));
        else bytes.push(224 | (c >> 12), 128 | ((c >> 6) & 63), 128 | (c & 63));
    }
    const bitLen = bytes.length * 8;
    bytes.push(0x80);
    while (bytes.length % 64 !== 56) bytes.push(0);
    bytes.push(0, 0, 0, 0, (bitLen >>> 24) & 255, (bitLen >>> 16) & 255, (bitLen >>> 8) & 255, bitLen & 255);

    const w = new Array(64);
    for (let i = 0; i < bytes.length; i += 64) {
        for (let t = 0; t < 16; t++) {
            w[t] = (bytes[i + t * 4] << 24) | (bytes[i + t * 4 + 1] << 16) | (bytes[i + t * 4 + 2] << 8) | bytes[i + t * 4 + 3];
        }
        for (let t = 16; t < 64; t++) {
            const s0 = rrot(w[t - 15], 7) ^ rrot(w[t - 15], 18) ^ (w[t - 15] >>> 3);
            const s1 = rrot(w[t - 2], 17) ^ rrot(w[t - 2], 19) ^ (w[t - 2] >>> 10);
            w[t] = (w[t - 16] + s0 + w[t - 7] + s1) | 0;
        }
        let [a, b, c, d, e, f, g, h] = H;
        for (let t = 0; t < 64; t++) {
            const S1 = rrot(e, 6) ^ rrot(e, 11) ^ rrot(e, 25);
            const ch = (e & f) ^ (~e & g);
            const t1 = (h + S1 + ch + K[t] + w[t]) | 0;
            const S0 = rrot(a, 2) ^ rrot(a, 13) ^ rrot(a, 22);
            const maj = (a & b) ^ (a & c) ^ (b & c);
            const t2 = (S0 + maj) | 0;
            h = g; g = f; f = e; e = (d + t1) | 0; d = c; c = b; b = a; a = (t1 + t2) | 0;
        }
        H[0] = (H[0] + a) | 0; H[1] = (H[1] + b) | 0; H[2] = (H[2] + c) | 0; H[3] = (H[3] + d) | 0;
        H[4] = (H[4] + e) | 0; H[5] = (H[5] + f) | 0; H[6] = (H[6] + g) | 0; H[7] = (H[7] + h) | 0;
    }

    let hex = '';
    for (let i = 0; i < 8; i++) hex += ('00000000' + (H[i] >>> 0).toString(16)).slice(-8);
    return hex;
}

function solveInWorker(salt, target, max) {
    return new Promise((resolve) => {
        const src = `const sha256hex = ${sha256hex.toString()};`
            + 'self.onmessage = function (e) {'
            + '  const {salt, target, max} = e.data;'
            + '  for (let n = 0; n <= max; n++) { if (sha256hex(salt + n) === target) { self.postMessage(n); return; } }'
            + '  self.postMessage(-1);'
            + '};';
        const worker = new Worker(URL.createObjectURL(new Blob([src], {type: 'application/javascript'})));
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
        .then((payload) => { if (payload) field.dataset.solution = payload; })
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
