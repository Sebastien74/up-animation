/**
 * Split desktop-only media queries out of the front theme CSS.
 *
 * Many Bootstrap rules (min-width >= 992px utilities, lg/xl/xxl) never apply on mobile
 * yet ship in the render-blocking theme stylesheet. This moves every `@media` block whose
 * min-width is >= 992px into a separate `*-desktop.*.css` file, removing it from the base.
 * The base (lighter) is loaded render-blocking; the desktop file is loaded with
 * media="(min-width:992px)" so it never blocks the mobile first paint, while desktop keeps
 * the full styling. Runs after `encore production` (see package.json build script).
 */
const fs = require('fs');
const path = require('path');
const postcss = require('postcss');

const DIR = path.join(__dirname, '..', '..', '..', 'public', 'build', 'front', 'default');
const MIN_DESKTOP = 992;
const NAME_RE = /^front-default(\.[0-9a-f]+)?\.css$/;

if (!fs.existsSync(DIR)) {
    process.exit(0);
}

let totalSaved = 0;
for (const file of fs.readdirSync(DIR)) {
    if (!NAME_RE.test(file) || file.includes('-desktop.')) {
        continue;
    }
    const full = path.join(DIR, file);
    const root = postcss.parse(fs.readFileSync(full, 'utf8'));
    const desktop = postcss.root();

    root.walkAtRules('media', (at) => {
        const mins = [...at.params.matchAll(/min-width:\s*(\d+)px/g)].map((m) => parseInt(m[1], 10));
        if (mins.length > 0 && mins.every((v) => v >= MIN_DESKTOP)) {
            desktop.append(at.clone());
            at.remove();
        }
    });

    const baseCss = root.toString();
    const deskCss = desktop.toString();
    fs.writeFileSync(full, baseCss);
    const deskName = file.replace(/^(front-default)(\.|$)/, '$1-desktop$2');
    fs.writeFileSync(path.join(DIR, deskName), deskCss);
    totalSaved += deskCss.length;
    console.log(`[split-mobile-css] ${file}: desktop ${(deskCss.length / 1024).toFixed(0)}KB -> ${deskName}`);
}
console.log(`[split-mobile-css] total deferred from mobile critical path: ${(totalSaved / 1024).toFixed(0)}KB`);
