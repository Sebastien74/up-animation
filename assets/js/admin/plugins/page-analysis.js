import Modal from '../bootstrap/dist/modal';
import Tooltip from '../bootstrap/dist/tooltip';

/**
 * Analyze the current page front rendering (AJAX, preview mode) and display the
 * report in a modal with "open preview", "re-run" and "copy report" actions.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

const SEVERITY_LABELS = {high: 'Critique', medium: 'Moyen', low: 'Mineur', info: 'Info', ok: 'OK'};

let getLoader = function () {
    return document.getElementById('main-preloader') || document.body.querySelector('.main-preloader');
};

let toggleLoader = function (show) {
    let loader = getLoader();
    if (loader instanceof HTMLElement) {
        loader.classList.toggle('d-none', !show);
    }
};

/**
 * Build a plain-text version of the report from the embedded JSON data.
 */
let buildReportText = function (modalEl) {
    let dataEl = modalEl.querySelector('#page-analysis-data');
    if (!dataEl) {
        return modalEl.innerText;
    }
    let data;
    try {
        data = JSON.parse(dataEl.textContent);
    } catch (e) {
        return modalEl.innerText;
    }
    let meta = data.meta || {};
    let lines = [];
    lines.push('Analyse de la page — ' + (data.url && data.url.code ? data.url.code : '/') + ' (' + (data.url && data.url.locale ? data.url.locale.toUpperCase() : '') + ')');
    lines.push('Indice: ' + (data.score === null ? 'N/A' : data.score + '/100')
        + ' · ' + (meta.kb || 0) + ' Ko · ' + (meta.requests || 0) + ' req · ' + (meta.dom || 0) + ' DOM · ' + (meta.renderMs || 0) + ' ms');
    (data.groups || []).forEach(function (group) {
        lines.push('');
        lines.push('== ' + group.label + ' ==');
        (group.findings || []).forEach(function (finding) {
            lines.push('- [' + (SEVERITY_LABELS[finding.severity] || finding.severity) + '] ' + finding.label + ' — ' + finding.value);
            if (finding.reco) {
                lines.push('  → ' + finding.reco);
            }
        });
    });
    return lines.join('\n');
};

let fallbackCopy = function (text, onDone) {
    let area = document.createElement('textarea');
    area.value = text;
    area.style.position = 'fixed';
    area.style.opacity = '0';
    document.body.appendChild(area);
    area.select();
    try {
        document.execCommand('copy');
        onDone();
    } catch (e) {
        console.error(e.message);
    }
    area.remove();
};

let bindActions = function (modalEl) {
    let rerun = modalEl.querySelector('.analyze-rerun');
    if (rerun) {
        rerun.addEventListener('click', function () {
            let href = rerun.getAttribute('data-href');
            if (href) {
                run(href);
            }
        });
    }

    let copy = modalEl.querySelector('.analyze-copy');
    if (copy) {
        copy.addEventListener('click', function () {
            let text = buildReportText(modalEl);
            let onDone = function () {
                let span = copy.querySelector('span');
                let label = copy.getAttribute('data-copied-label') || 'OK';
                if (span) {
                    let previous = span.textContent;
                    span.textContent = label;
                    setTimeout(function () {
                        span.textContent = previous;
                    }, 1500);
                }
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(onDone).catch(function () {
                    fallbackCopy(text, onDone);
                });
            } else {
                fallbackCopy(text, onDone);
            }
        });
    }
};

let cleanup = function () {
    document.querySelectorAll('#modal-page-analysis').forEach(function (modal) {
        let instance = Modal.getInstance(modal);
        if (instance) {
            instance.dispose();
        }
        let wrapper = modal.closest('.modal-wrapper');
        (wrapper || modal).remove();
    });
    document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
        backdrop.remove();
    });
    document.body.classList.remove('modal-open');
    document.body.removeAttribute('style');
};

let run = function (href) {
    toggleLoader(true);
    let url = href + (href.indexOf('?') > -1 ? '&ajax=true' : '?ajax=true');

    fetch(url, {method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(function (response) {
            if (!response.ok) {
                return response.text().then(function (text) {
                    throw text;
                });
            }
            return response.json();
        })
        .then(function (response) {
            toggleLoader(false);
            if (!response.html) {
                return;
            }
            cleanup();

            let wrapper = document.createElement('div');
            wrapper.innerHTML = response.html.trim();
            document.body.appendChild(wrapper);

            let modalEl = document.getElementById('modal-page-analysis');
            if (modalEl) {
                Modal.getOrCreateInstance(modalEl).show();
                modalEl.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (tip) {
                    Tooltip.getOrCreateInstance(tip);
                });
                bindActions(modalEl);
                modalEl.addEventListener('hidden.bs.modal', function () {
                    document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
                        backdrop.remove();
                    });
                    document.body.removeAttribute('style');
                    wrapper.remove();
                });
            }
        })
        .catch(function (errors) {
            toggleLoader(false);
            import('../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(function (error) {
                console.error(error.message);
            });
        });
};

export default function (e, el) {
    run(el.getAttribute('href'));
}
