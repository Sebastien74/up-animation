/**
 * Page analysis dashboard: run preview analysis per row (AJAX) and "analyze all"
 * sequentially with a progress bar. Admin/preview only.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

const container = document.getElementById('page-analysis');

if (container) {

    const scoreClass = function (score) {
        if (score === null || score === undefined) {
            return 'bg-secondary';
        }
        if (score >= 90) {
            return 'bg-success';
        }
        if (score >= 60) {
            return 'bg-warning';
        }
        return 'bg-danger';
    };

    const updateRow = function (row, data) {
        const scoreEl = row.querySelector('.pa-score');
        const kbEl = row.querySelector('.pa-kb');
        const issuesEl = row.querySelector('.pa-issues');
        const dateEl = row.querySelector('.pa-date');

        if (!data.ok) {
            if (scoreEl) {
                scoreEl.className = 'badge pa-score bg-danger';
                scoreEl.textContent = 'KO';
            }
            return;
        }
        if (scoreEl) {
            scoreEl.className = 'badge pa-score ' + scoreClass(data.score);
            scoreEl.textContent = data.score === null ? '—' : data.score;
        }
        if (kbEl) {
            kbEl.textContent = (data.kb || 0) + ' Ko';
        }
        if (issuesEl) {
            issuesEl.textContent = (data.high || 0) + ' / ' + (data.medium || 0) + ' / ' + (data.low || 0);
        }
        if (dateEl) {
            dateEl.textContent = data.date || '';
        }
    };

    const runRow = function (row) {
        const url = row.getAttribute('data-run-url');
        const button = row.querySelector('.pa-run');
        if (!url) {
            return Promise.resolve();
        }
        if (button) {
            button.disabled = true;
            button.classList.add('disabled');
        }
        row.classList.add('opacity-75');

        return fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(response => response.ok ? response.json() : {ok: false})
            .then(data => updateRow(row, data))
            .catch(() => updateRow(row, {ok: false}))
            .finally(() => {
                row.classList.remove('opacity-75');
                if (button) {
                    button.disabled = false;
                    button.classList.remove('disabled');
                }
            });
    };

    // Single row analysis.
    container.addEventListener('click', function (e) {
        const button = e.target.closest('.pa-run');
        if (!button) {
            return;
        }
        e.preventDefault();
        const row = button.closest('.pa-row');
        if (row) {
            runRow(row);
        }
    });

    // Analyze all (sequential to avoid overloading the server).
    const runAllButton = container.querySelector('.pa-run-all');
    const progressWrap = container.querySelector('.pa-progress-wrap');
    const progressBar = container.querySelector('.pa-progress');
    const statusEl = container.querySelector('.pa-status');

    if (runAllButton) {
        runAllButton.addEventListener('click', function () {
            const rows = Array.from(container.querySelectorAll('.pa-row')).filter(row => !row.classList.contains('d-none'));
            if (!rows.length || runAllButton.disabled) {
                return;
            }
            runAllButton.disabled = true;
            runAllButton.classList.add('disabled');
            if (progressWrap) {
                progressWrap.classList.remove('d-none');
            }

            let done = 0;
            const total = rows.length;
            const next = function () {
                if (!rows.length) {
                    if (statusEl) {
                        statusEl.textContent = total + ' page(s) analysée(s).';
                    }
                    runAllButton.disabled = false;
                    runAllButton.classList.remove('disabled');
                    return;
                }
                const row = rows.shift();
                if (statusEl) {
                    statusEl.textContent = 'Analyse ' + (done + 1) + ' / ' + total + '…';
                }
                runRow(row).finally(() => {
                    done += 1;
                    if (progressBar) {
                        progressBar.style.width = Math.round((done / total) * 100) + '%';
                    }
                    next();
                });
            };
            next();
        });
    }

    // Client-side filter.
    const filter = container.querySelector('.pa-filter');
    if (filter) {
        filter.addEventListener('input', function () {
            const term = filter.value.trim().toLowerCase();
            container.querySelectorAll('.pa-row').forEach(function (row) {
                const haystack = row.getAttribute('data-search') || '';
                row.classList.toggle('d-none', term !== '' && haystack.indexOf(term) === -1);
            });
        });
    }
}
