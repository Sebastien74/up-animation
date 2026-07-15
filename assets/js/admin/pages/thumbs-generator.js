/**
 * Générateur de vignettes (crawl du sitemap).
 *
 * Récupère toutes les URLs du sitemap via un endpoint admin, puis les parcourt
 * une à une. Pour chaque page, on lit le HTML et on déclenche les fragments
 * `front_media_loader` (hinclude) encore présents : c'est ce fetch qui génère
 * réellement les variantes de vignettes côté serveur et les marque comme faites.
 * Une barre de progression affiche l'avancement et l'URL en cours.
 */

const FRAGMENT_CONCURRENCY = 4;

/**
 * Déclenche un fragment mediaLoader (best-effort, les échecs sont ignorés).
 *
 * @param {string} url
 * @returns {Promise<void>}
 */
async function fetchFragment(url) {
    try {
        await fetch(url, {credentials: 'same-origin'});
    } catch (e) {
        // Génération best-effort : un fragment en échec ne bloque pas le reste.
    }
}

/**
 * Traite une page : récupère son HTML et déclenche les fragments de génération.
 *
 * @param {string} pageUrl
 * @returns {Promise<number>} nombre de fragments déclenchés
 */
async function processPage(pageUrl) {
    const response = await fetch(pageUrl, {credentials: 'same-origin'});
    if (!response.ok) {
        return 0;
    }

    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const fragments = [...doc.querySelectorAll('[src*="_fragment"]')]
        .map(el => el.getAttribute('src'))
        .filter(src => src && src.includes('mediaLoader'))
        .map(src => new URL(src, pageUrl).href);
    const unique = [...new Set(fragments)];

    for (let i = 0; i < unique.length; i += FRAGMENT_CONCURRENCY) {
        await Promise.all(unique.slice(i, i + FRAGMENT_CONCURRENCY).map(fetchFragment));
    }

    return unique.length;
}

export default function thumbsGenerator() {
    const button = document.getElementById('thumbs-generate');
    const panel = document.getElementById('thumbs-progress');
    if (!button || !panel) {
        return;
    }

    const bar = panel.querySelector('.progress-bar');
    const countLabel = panel.querySelector('.thumbs-progress-count');
    const currentLabel = panel.querySelector('.thumbs-progress-current');

    const setProgress = (percent) => {
        bar.style.width = `${percent}%`;
        panel.querySelector('.progress').setAttribute('aria-valuenow', String(percent));
    };

    button.addEventListener('click', async () => {
        const endpoint = button.dataset.urlsEndpoint;
        if (!endpoint || button.disabled) {
            return;
        }

        button.disabled = true;
        panel.classList.remove('d-none');
        bar.classList.add('progress-bar-animated');
        currentLabel.textContent = '';
        setProgress(0);

        let urls = [];
        try {
            const response = await fetch(endpoint, {credentials: 'same-origin'});
            const data = await response.json();
            urls = Array.isArray(data.urls) ? data.urls : [];
        } catch (e) {
            urls = [];
        }

        const total = urls.length;
        countLabel.textContent = `0 / ${total}`;

        if (total === 0) {
            setProgress(100);
            bar.classList.remove('progress-bar-animated');
            currentLabel.textContent = button.dataset.emptyLabel || '';
            button.disabled = false;
            return;
        }

        let done = 0;
        let errors = 0;
        for (const url of urls) {
            currentLabel.textContent = url;
            try {
                await processPage(url);
            } catch (e) {
                errors += 1;
            }
            done += 1;
            countLabel.textContent = `${done} / ${total}`;
            setProgress(Math.round((done / total) * 100));
        }

        bar.classList.remove('progress-bar-animated');
        const doneLabel = button.dataset.doneLabel || '';
        const errorLabel = button.dataset.errorLabel || '';
        currentLabel.textContent = errors ? `${doneLabel} — ${errors} ${errorLabel}` : doneLabel;
        button.disabled = false;
    });
}
