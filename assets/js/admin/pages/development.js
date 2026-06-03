import '../../../scss/admin/pages/development.scss';

let importData = function (progress) {
    let indexLinks = document.getElementById('import-index-links');
    let index = document.getElementById('index-import-data');
    let list = document.getElementById('entities-to-import');
    let item = list.querySelector('.item.to-import');
    let progressCard = document.getElementById('progress-card');
    let progressBar = progressCard.querySelector('.progress-bar');
    let successCard = document.getElementById('success-card');
    let counterWrap = progressCard.querySelector('.count');
    let nameWrap = progressCard.querySelector('.name');
    let itemsLength = parseInt(counterWrap.dataset.count);
    if (!indexLinks.classList.contains('d-none')) {
        indexLinks.classList.add('d-none');
    }
    if (item) {
        let xHttp = new XMLHttpRequest()
        xHttp.open("GET", item.dataset.path, true)
        xHttp.send()
        xHttp.onload = function (e) {
            if (this.readyState === 4 && this.status === 200) {
                nameWrap.innerHTML = item.dataset.name;
                item.remove();
                let percent = (progress * 100) / itemsLength;
                progressBar.setAttribute('aria-valuenow', percent.toString());
                progressBar.setAttribute('style', "width: " + percent + "%");
                counterWrap.innerHTML = progress.toString();
                progress++;
                importData(progress);
            }
        }
    } else {
        setTimeout(function () {
            progressCard.remove();
            successCard.classList.remove('d-none');
            setTimeout(function () {
                index.remove();
                indexLinks.classList.remove('d-none');
            }, 3000);
        }, 1500);
    }
}

let importBoutons = document.querySelectorAll('.import-data-btn');
importBoutons.forEach(function (btn) {
    btn.onclick = function () {
        let xHttp = new XMLHttpRequest();
        xHttp.open("GET", btn.dataset.path, true);
        xHttp.send();
        xHttp.onload = function (e) {
            if (this.readyState === 4 && this.status === 200) {
                let response = JSON.parse(this.response);
                let importWrap = document.getElementById('ajax-import-wrap');
                importWrap.innerHTML = response.html;
                importWrap.classList.remove('d-none');
                importData(1);
            }
        }
    }
});

/**
 * Recount the global / unique deprecation tiles from the current table rows.
 */
const updateDeprecationSummary = () => {
    const tbody = document.getElementById('dep-tbody');
    if (!tbody) {
        return;
    }
    const rows = tbody.querySelectorAll('tr');
    const total = document.getElementById('dep-total');
    const unique = document.getElementById('dep-unique');
    if (total) {
        total.textContent = rows.length.toString();
    }
    if (unique) {
        const messages = new Set();
        rows.forEach((row) => {
            const message = row.querySelector('.dep-message');
            if (message) {
                messages.add(message.textContent);
            }
        });
        unique.textContent = messages.size.toString();
    }
};

/**
 * Complete deprecation scan (PHPStan), run in browser-driven batches with a real progress bar.
 * Scan rows are appended to the existing list (journal rows are kept) and persisted server-side.
 */
const depScanBtn = document.getElementById('dep-scan-btn');
if (depScanBtn) {
    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };

    const appendScanRows = (findings) => {
        const tbody = document.getElementById('dep-tbody');
        const rows = findings
            .slice()
            .sort((a, b) => (a.area + a.package).localeCompare(b.area + b.package))
            .map((f) => '<tr data-source="scan">'
                + '<td class="align-middle px-4" data-label="Source"><span class="dep-source dep-source-scan">scan</span></td>'
                + '<td class="align-middle px-4" data-label="Zone"><span class="dep-pkg">' + escapeHtml(f.area) + '</span></td>'
                + '<td class="align-middle px-4" data-label="Paquet"><span class="dep-pkg">' + escapeHtml(f.package) + '</span></td>'
                + '<td class="align-middle px-4" data-label="Message"><span class="dep-message">' + escapeHtml(f.message) + '</span></td>'
                + '<td class="align-middle px-4" data-label="Emplacement"><span class="dep-loc">' + escapeHtml(f.file) + ':' + f.line + '</span></td>'
                + '</tr>')
            .join('');
        tbody.insertAdjacentHTML('afterbegin', rows);

        const byArea = {};
        const byPackage = {};
        findings.forEach((f) => {
            byArea[f.area] = (byArea[f.area] || 0) + 1;
            byPackage[f.package] = (byPackage[f.package] || 0) + 1;
        });
        const chip = (map) => Object.entries(map)
            .sort((a, b) => b[1] - a[1])
            .map(([k, v]) => '<span class="dep-chip">' + escapeHtml(k) + ' <strong>' + v + '</strong></span>')
            .join('');
        const chips = document.getElementById('dep-chips');
        chips.innerHTML = chip(byArea) + chip(byPackage);
        chips.classList.remove('d-none');

        const empty = document.getElementById('dep-empty');
        if (empty) {
            empty.classList.add('d-none');
        }
        updateDeprecationSummary();
    };

    const runDeprecationScan = async () => {
        const url = depScanBtn.dataset.scanUrl;
        const token = depScanBtn.dataset.token;
        const size = parseInt(depScanBtn.dataset.size, 10) || 300;

        const wrap = document.getElementById('dep-progress-wrap');
        const bar = document.getElementById('dep-progress-bar');
        const count = document.getElementById('dep-progress-count');
        const label = document.getElementById('dep-progress-label');
        const feedback = document.getElementById('dep-feedback');

        depScanBtn.disabled = true;
        feedback.innerHTML = '';
        bar.classList.add('progress-bar-striped', 'progress-bar-animated');
        bar.style.width = '0%';
        wrap.classList.remove('d-none');

        // Drop the previous scan trace (kept journal rows are left untouched).
        document.querySelectorAll('#dep-tbody tr[data-source="scan"]').forEach((row) => row.remove());
        updateDeprecationSummary();

        const findings = [];
        let offset = 0;
        let done = false;
        let lastDiag = null;

        try {
            while (!done) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
                    body: JSON.stringify({ offset, size }),
                });
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }
                if (data.diag) {
                    lastDiag = data.diag;
                }

                findings.push(...(data.findings || []));
                const percent = data.total > 0 ? Math.round((data.processed / data.total) * 100) : 100;
                bar.style.width = percent + '%';
                bar.parentElement.setAttribute('aria-valuenow', percent.toString());
                count.textContent = data.processed + ' / ' + data.total;

                offset = data.processed;
                done = data.done;
            }

            bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
            label.textContent = 'Scan terminé';

            if (findings.length > 0) {
                appendScanRows(findings);
                feedback.innerHTML = '<div class="alert alert-success mt-3">Scan terminé : ' + findings.length + ' dépréciation(s) ajoutée(s) à la liste.</div>';
            } else if (lastDiag) {
                feedback.innerHTML = '<div class="alert alert-warning mt-3">L\'analyse n\'a renvoyé aucun résultat exploitable (code ' + escapeHtml(lastDiag.exitCode) + ').'
                    + (lastDiag.stderr ? '<pre class="dep-message mt-2 mb-0">' + escapeHtml(lastDiag.stderr) + '</pre>' : '') + '</div>';
            } else {
                feedback.innerHTML = '<div class="alert alert-success mt-3">Aucune dépréciation détectée par l\'analyse statique.</div>';
            }
        } catch (error) {
            label.textContent = 'Échec du scan';
            feedback.innerHTML = '<div class="alert alert-danger mt-3">Le scan a échoué : ' + escapeHtml(error.message) + '</div>';
        } finally {
            depScanBtn.disabled = false;
        }
    };

    depScanBtn.addEventListener('click', runDeprecationScan);
}

/**
 * Runtime crawl: visit every URL one by one, show the current URL, append findings to the list.
 */
const depCrawlBtn = document.getElementById('dep-crawl-btn');
if (depCrawlBtn) {
    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };

    const appendCrawlRows = (findings) => {
        if (findings.length === 0) {
            return;
        }
        const tbody = document.getElementById('dep-tbody');
        const rows = findings.map((f) => '<tr data-source="crawl">'
            + '<td class="align-middle px-4" data-label="Source"><span class="dep-source dep-source-crawl">crawl</span></td>'
            + '<td class="align-middle px-4" data-label="Zone"><span class="dep-pkg">' + escapeHtml(f.area) + '</span></td>'
            + '<td class="align-middle px-4" data-label="Paquet"><span class="dep-pkg">' + escapeHtml(f.package) + '</span></td>'
            + '<td class="align-middle px-4" data-label="Message"><span class="dep-message">' + escapeHtml(f.message) + '</span></td>'
            + '<td class="align-middle px-4" data-label="Emplacement"><span class="dep-loc">' + escapeHtml(f.location) + '</span></td>'
            + '</tr>').join('');
        tbody.insertAdjacentHTML('afterbegin', rows);
        const empty = document.getElementById('dep-empty');
        if (empty) {
            empty.classList.add('d-none');
        }
        updateDeprecationSummary();
    };

    const runCrawl = async () => {
        const url = depCrawlBtn.dataset.crawlUrl;
        const token = depCrawlBtn.dataset.token;
        const total = parseInt(depCrawlBtn.dataset.total, 10) || 0;

        const wrap = document.getElementById('dep-crawl-wrap');
        const bar = document.getElementById('dep-crawl-bar');
        const count = document.getElementById('dep-crawl-count');
        const label = document.getElementById('dep-crawl-label');
        const current = document.getElementById('dep-crawl-current');
        const feedback = document.getElementById('dep-feedback');

        depCrawlBtn.disabled = true;
        feedback.innerHTML = '';
        bar.classList.add('progress-bar-striped', 'progress-bar-animated');
        bar.style.width = '0%';
        wrap.classList.remove('d-none');
        current.textContent = '';

        document.querySelectorAll('#dep-tbody tr[data-source="crawl"]').forEach((row) => row.remove());
        updateDeprecationSummary();

        let totalFound = 0;
        let index = 0;
        let done = total === 0;

        try {
            while (!done) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
                    body: JSON.stringify({ index }),
                });
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }

                if (data.url) {
                    current.textContent = data.url;
                }
                appendCrawlRows(data.findings || []);
                totalFound += (data.findings || []).length;

                const percent = data.total > 0 ? Math.round((data.processed / data.total) * 100) : 100;
                bar.style.width = percent + '%';
                bar.parentElement.setAttribute('aria-valuenow', percent.toString());
                count.textContent = data.processed + ' / ' + data.total;

                index = data.processed;
                done = data.done;
            }

            bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
            label.textContent = 'Crawl terminé';
            current.textContent = '';
            feedback.innerHTML = '<div class="alert alert-success mt-3">Crawl terminé : ' + totalFound + ' dépréciation(s) runtime ajoutée(s) à la liste.</div>';
        } catch (error) {
            label.textContent = 'Échec du crawl';
            feedback.innerHTML = '<div class="alert alert-danger mt-3">Le crawl a échoué : ' + escapeHtml(error.message) + '</div>';
        } finally {
            depCrawlBtn.disabled = false;
        }
    };

    depCrawlBtn.addEventListener('click', runCrawl);
}

/**
 * Clear the deprecation logs (runtime journal + scan & crawl traces), then reload.
 */
const depClearBtn = document.getElementById('dep-clear-btn');
if (depClearBtn) {
    depClearBtn.addEventListener('click', async () => {
        depClearBtn.disabled = true;
        try {
            const body = new FormData();
            body.append('_token', depClearBtn.dataset.token);
            const response = await fetch(depClearBtn.dataset.clearUrl, { method: 'POST', body });
            if (response.ok) {
                window.location.reload();
                return;
            }
        } catch (error) {
            // fall through to re-enable the button
        }
        depClearBtn.disabled = false;
    });
}

/**
 * Phpinfo search engine with highlight
 */
const phpinfoSearch = document.getElementById('phpinfo-search');
if (phpinfoSearch) {
    const handleSearch = function () {
        const query = phpinfoSearch.value.toLowerCase().trim();
        const sections = document.querySelectorAll('.phpinfo-section');

        sections.forEach(function (section) {
            const rows = section.querySelectorAll('tbody tr');
            let sectionHasVisibleRow = false;

            rows.forEach(function (row) {
                const cells = row.querySelectorAll('td:not(.d-none)');
                let rowMatches = false;

                cells.forEach(function (cell) {
                    // Store original text if not already stored
                    if (!cell.dataset.originalContent) {
                        cell.dataset.originalContent = cell.innerHTML;
                    }

                    const originalHTML = cell.dataset.originalContent;
                    const text = cell.textContent.toLowerCase();

                    if (query !== '' && text.includes(query)) {
                        rowMatches = true;
                        // Highlight matches
                        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                        cell.innerHTML = originalHTML.replace(regex, '<span class="text-primary fw-bold">$1</span>');
                    } else {
                        // Restore original content
                        cell.innerHTML = originalHTML;
                        if (text.includes(query) || query === '') {
                            rowMatches = true;
                        }
                    }
                });

                if (rowMatches || query === '') {
                    row.style.display = '';
                    sectionHasVisibleRow = true;
                } else {
                    row.style.display = 'none';
                }
            });

            if (sectionHasVisibleRow || query === '') {
                section.style.display = '';
            } else {
                section.style.display = 'none';
            }
        });
    };

    phpinfoSearch.addEventListener('input', handleSearch);

    // Filter on load if value is present (e.g., on page refresh)
    if (phpinfoSearch.value.trim() !== '') {
        handleSearch();
    }
}

// $('#cities-bio').find('.bio-places').each(function () {
//
//     let el = $(this);
//     let data = $(this);
//     // let input = $('#bio-places');
//     // setTimeout(function () {
//     //     console.log(el);
//     //     input.val(el.data('city') + ' ' + el.data('zipcode'));
//     //     $('body').simulateKeyPress('x');
//     // }, 3000);
//
//     let placesAutocomplete = places({
//         appId: 'plIZX27D5L3L',
//         apiKey: '61cd64b7ddb5453f558240e9e5a17bc0',
//         language: locale,
//         type: 'townhall',
//         container: document.querySelector('#' + el.attr('id'))
//     });
//
//     el.val(el.data('city'));
//
//     placesAutocomplete.on('change', function (e) {
//
//         console.log(e);
//         // $('input.latitude').val(e.suggestion.latlng.lat);
//         // $('input.longitude').val(e.suggestion.latlng.lng);
//         // $('input.zip-code').val(e.suggestion.postcode);
//         // $('input.department').val(e.suggestion.county);
//         // $('input.region').val(e.suggestion.administrative);
//         //
//         // let address = e.suggestion.name ? e.suggestion.name : e.suggestion.value;
//         // $('input.address').val(address);
//         //
//         // let city = e.suggestion.city ? e.suggestion.city : e.suggestion.name;
//         // $('input.city').val(city);
//         //
//         // let country = e.suggestion.countryCode;
//         // countryEl.val(country.toUpperCase());
//         // countryEl.select2().trigger('change');
//     });
// });