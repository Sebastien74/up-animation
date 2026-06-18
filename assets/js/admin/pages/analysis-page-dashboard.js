/**
 * PageSpeed Insights dashboard: run real Lighthouse measurements per row (AJAX) and
 * "measure all" sequentially with a progress bar and a daily quota guard. Admin only.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

const container = document.getElementById('analysis-page');

if (container) {

    const setButtonLoading = function (button, loading) {
        if (!button) {
            return;
        }
        const icon = button.querySelector('i, .spinner-border');
        if (loading) {
            button.disabled = true;
            button.classList.add('disabled');
            if (icon && !icon.classList.contains('spinner-border')) {
                icon.dataset.iconClass = icon.className;
                icon.className = 'spinner-border spinner-border-sm';
                icon.setAttribute('role', 'status');
            }
            return;
        }
        button.disabled = false;
        button.classList.remove('disabled');
        if (icon && icon.classList.contains('spinner-border') && icon.dataset.iconClass) {
            icon.className = icon.dataset.iconClass;
            icon.removeAttribute('role');
        }
    };

    const mainPreloader = document.getElementById('main-preloader');
    const progressWrap = container.querySelector('.pa-progress-wrap');
    const progressBar = container.querySelector('.pa-progress');
    const statusEl = container.querySelector('.pa-status');

    // PageSpeed Insights: real Lighthouse runs (slow), admin-triggered per row or for all.
    // Daily quota guard: keep the remaining-measurement counter in sync and disable the
    // run buttons once there is not enough quota left (the server enforces it too).
    const psiTotal = parseInt(container.getAttribute('data-psi-total') || '0', 10);
    let psiRemaining = parseInt(container.getAttribute('data-psi-remaining') || '0', 10);
    const psiQuotaCount = container.querySelector('.pa-psi-quota-count');

    const applyPsiQuota = function (remaining) {
        if (typeof remaining === 'number' && !isNaN(remaining)) {
            psiRemaining = remaining;
        }
        if (psiQuotaCount) {
            psiQuotaCount.textContent = psiRemaining;
        }
        container.querySelectorAll('.pa-psi-run').forEach(function (btn) {
            btn.disabled = psiRemaining < 1;
        });
        const bulk = container.querySelector('.pa-psi-run-all');
        if (bulk) {
            const blocked = psiRemaining < psiTotal;
            bulk.disabled = blocked;
            bulk.classList.toggle('is-quota-blocked', blocked);
        }
    };

    const psiPillState = function (value) {
        if (value === null || value === undefined || value === '') {
            return 'is-none';
        }
        if (value >= 90) {
            return 'is-good';
        }
        if (value >= 50) {
            return 'is-warn';
        }
        return 'is-bad';
    };

    const psiPillHtml = function (letter, value) {
        const v = (value === null || value === undefined || value === '') ? null : value;
        return '<span class="pa-psi-pill ' + psiPillState(v) + '"><span class="pa-psi-strat">' + letter + '</span>'
            + (v === null ? '-' : v) + '</span>';
    };

    const updatePsiCell = function (cell, data) {
        if (!cell || !data || !data.ok) {
            return;
        }
        const pills = cell.querySelector('.pa-psi-pills');
        if (pills) {
            pills.innerHTML = psiPillHtml('M', data.perfMobile) + psiPillHtml('D', data.perfDesktop);
        }
        cell.dataset.sort = (data.perfMobile === null || data.perfMobile === undefined) ? -1 : data.perfMobile;
        const row = cell.closest('.pa-row');
        const dateEl = row ? row.querySelector('.pa-date') : null;
        if (dateEl && data.date) {
            const dateSpan = dateEl.querySelector('.text-nowrap') || dateEl;
            dateSpan.textContent = data.date;
            dateEl.dataset.sort = Math.floor(Date.now() / 1000);
        }
    };

    const runPsiRow = function (row) {
        const cell = row.querySelector('.pa-psi-cell');
        const button = cell ? cell.querySelector('.pa-psi-run') : null;
        const url = button ? button.getAttribute('data-psi-url') : null;
        if (!url) {
            return Promise.resolve();
        }
        setButtonLoading(button, true);
        row.classList.add('pa-psi-running');

        let remaining;
        return fetch(url, {method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(response => response.ok ? response.json() : {ok: false})
            .then(data => {
                updatePsiCell(cell, data);
                if (data && data.remaining !== undefined) {
                    remaining = data.remaining;
                }
            })
            .catch(() => {})
            .finally(() => {
                row.classList.remove('pa-psi-running');
                setButtonLoading(button, false);
                // Re-apply quota state last so an exhausted button is not re-enabled.
                applyPsiQuota(remaining);
            });
    };

    container.addEventListener('click', function (e) {
        const button = e.target.closest('.pa-psi-run');
        if (!button) {
            return;
        }
        e.preventDefault();
        const row = button.closest('.pa-row');
        if (row) {
            if (mainPreloader) {
                mainPreloader.classList.remove('d-none');
            }
            runPsiRow(row).finally(function () {
                if (mainPreloader) {
                    mainPreloader.classList.add('d-none');
                }
            });
        }
    });

    const psiRunAll = container.querySelector('.pa-psi-run-all');
    if (psiRunAll) {
        psiRunAll.addEventListener('click', function () {
            const rows = Array.from(container.querySelectorAll('.pa-row'))
                .filter(row => !row.classList.contains('d-none') && row.querySelector('.pa-psi-run'));
            if (!rows.length || psiRunAll.disabled) {
                return;
            }
            psiRunAll.disabled = true;
            psiRunAll.classList.add('disabled');
            if (progressWrap) {
                progressWrap.classList.remove('d-none');
            }
            if (mainPreloader) {
                mainPreloader.classList.remove('d-none');
            }

            let done = 0;
            const total = rows.length;
            const next = function () {
                if (!rows.length) {
                    if (statusEl) {
                        statusEl.textContent = total + ' mesure(s) PageSpeed terminée(s).';
                    }
                    if (mainPreloader) {
                        mainPreloader.classList.add('d-none');
                    }
                    psiRunAll.disabled = false;
                    psiRunAll.classList.remove('disabled');
                    return;
                }
                const row = rows.shift();
                if (statusEl) {
                    statusEl.textContent = 'PageSpeed ' + (done + 1) + ' / ' + total + '…';
                }
                runPsiRow(row).finally(() => {
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

    // Client-side filters: free-text search + language selector (combined).
    const filter = container.querySelector('.pa-filter');
    const langFilter = container.querySelector('.pa-lang-filter');

    const applyFilters = function () {
        const term = filter ? filter.value.trim().toLowerCase() : '';
        const lang = langFilter ? langFilter.value.trim().toLowerCase() : '';
        container.querySelectorAll('.pa-row').forEach(function (row) {
            const haystack = row.getAttribute('data-search') || '';
            const locale = (row.getAttribute('data-locale') || '').toLowerCase();
            const matchesTerm = term === '' || haystack.indexOf(term) !== -1;
            const matchesLang = lang === '' || locale === lang;
            row.classList.toggle('d-none', !(matchesTerm && matchesLang));
        });
    };

    if (filter) {
        filter.addEventListener('input', applyFilters);
    }
    if (langFilter) {
        langFilter.addEventListener('change', applyFilters);
    }
    // Apply the server-preselected language (site default locale) on load.
    if (langFilter && langFilter.value) {
        applyFilters();
    }

    // Column sorting: click a header to reorder rows by that column.
    const sortTable = container.querySelector('table.responsive-cards');
    const sortBody = sortTable ? sortTable.querySelector('tbody') : null;

    if (sortBody) {
        const headers = sortTable.querySelectorAll('thead th.pa-sortable');
        headers.forEach(function (th) {
            th.addEventListener('click', function () {
                const colIndex = Array.prototype.indexOf.call(th.parentNode.children, th);
                const numeric = 'num' === th.getAttribute('data-sort-type');
                const asc = !th.classList.contains('sort-asc');

                headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
                th.classList.add(asc ? 'sort-asc' : 'sort-desc');

                Array.from(sortBody.querySelectorAll('.pa-row'))
                    .sort(function (a, b) {
                        const av = a.children[colIndex] ? a.children[colIndex].getAttribute('data-sort') || '' : '';
                        const bv = b.children[colIndex] ? b.children[colIndex].getAttribute('data-sort') || '' : '';
                        const cmp = numeric
                            ? (parseFloat(av) || 0) - (parseFloat(bv) || 0)
                            : av.localeCompare(bv, 'fr');
                        return asc ? cmp : -cmp;
                    })
                    .forEach(row => sortBody.appendChild(row));
            });
        });
    }
}

// Delete confirmation modal (shared by "delete all" and per-page triggers).
// The clicked trigger carries the target action URL and scope; we wire them
// into the form on show so a single modal serves every delete.
const deleteModal = document.getElementById('pa-delete-modal');

if (deleteModal) {
    const deleteForm = deleteModal.querySelector('.pa-delete-form');
    const deleteText = deleteModal.querySelector('.pa-delete-text');

    const redirectInput = deleteForm ? deleteForm.querySelector('.pa-delete-redirect') : null;

    deleteModal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }
        const action = trigger.getAttribute('data-pa-action');
        const scope = trigger.getAttribute('data-pa-scope');
        const label = trigger.getAttribute('data-pa-label');

        if (deleteForm && action) {
            deleteForm.setAttribute('action', action);
        }
        if (redirectInput) {
            redirectInput.value = trigger.getAttribute('data-pa-return') || '';
        }
        if (deleteText) {
            deleteText.textContent = scope === 'page' && label
                ? 'Supprimer les mesures PageSpeed de « ' + label + ' » ? Cette action est irréversible.'
                : 'Supprimer toutes les mesures PageSpeed enregistrées ? Cette action est irréversible.';
        }
    });
}

// Detail page: run PageSpeed then reload to show the fresh report (slow, real Lighthouse).
const detailContainer = document.getElementById('analysis-page-detail');

if (detailContainer) {
    // Clicking a category gauge smooth-scrolls to its audit section.
    detailContainer.addEventListener('click', function (e) {
        const gauge = e.target.closest('.psi-gauge-link');
        if (!gauge) {
            return;
        }
        const section = document.getElementById(gauge.getAttribute('data-scroll-to') || '');
        if (section) {
            section.scrollIntoView({behavior: 'smooth', block: 'start'});
        }
    });

    const psiButton = detailContainer.querySelector('.pa-psi-run');
    const psiUrl = detailContainer.getAttribute('data-psi-url');

    if (psiButton && psiUrl) {
        const preloader = document.getElementById('main-preloader');
        psiButton.addEventListener('click', function () {
            psiButton.disabled = true;
            psiButton.classList.add('disabled');
            const span = psiButton.querySelector('span');
            const running = psiButton.getAttribute('data-running-label');
            if (span && running) {
                span.textContent = running;
            }
            if (preloader) {
                preloader.classList.remove('d-none');
            }
            fetch(psiUrl, {method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(response => response.ok ? response.json() : {ok: false})
                .then(function (data) {
                    if (data && data.ok) {
                        window.location.reload();
                        return;
                    }
                    if (preloader) {
                        preloader.classList.add('d-none');
                    }
                    psiButton.disabled = false;
                    psiButton.classList.remove('disabled');
                    import('../core/errors').then(({default: displayErrors}) => {
                        new displayErrors(data && data.error ? data.error : 'Erreur PageSpeed');
                    }).catch(error => console.error(error.message));
                })
                .catch(function () {
                    if (preloader) {
                        preloader.classList.add('d-none');
                    }
                    psiButton.disabled = false;
                    psiButton.classList.remove('disabled');
                });
        });
    }

    // Copy the hidden PageSpeed Markdown source to the clipboard.
    const wireCopyButton = function (button, source) {
        if (!button || !source) {
            return;
        }
        const flashCopied = function () {
            const span = button.querySelector('span');
            const label = button.getAttribute('data-copied-label') || 'OK';
            if (span) {
                const previous = span.textContent;
                span.textContent = label;
                setTimeout(() => { span.textContent = previous; }, 1500);
            }
        };
        const fallbackCopy = function (text) {
            source.classList.remove('d-none');
            source.select();
            try {
                document.execCommand('copy');
                flashCopied();
            } catch (e) {
                console.error(e.message);
            }
            source.classList.add('d-none');
        };
        button.addEventListener('click', function () {
            const text = source.value;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(flashCopied).catch(() => fallbackCopy(text));
            } else {
                fallbackCopy(text);
            }
        });
    };

    wireCopyButton(detailContainer.querySelector('.psi-copy-md'), document.getElementById('psi-md-source'));
}
