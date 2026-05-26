/**
 * Admin analytics stats page.
 *
 * Loads aggregated data from the StatsController JSON endpoint and
 * renders Chart.js charts. Lazy-loaded entrypoint, never bundled in
 * the global admin runtime.
 */

import { Chart, registerables } from 'chart.js';
import { MatrixController, MatrixElement } from 'chartjs-chart-matrix';
import 'chartjs-adapter-date-fns';
import '../../../scss/admin/pages/stats.scss';

Chart.register(...registerables, MatrixController, MatrixElement);

const PALETTE = {
    primary: '#4f46e5',
    primarySoft: '#a5b4fc',
    primaryDeep: '#312e81',
    muted: '#94a3b8',
    mutedSoft: '#cbd5e1',
    text: '#1f2937',
    textSoft: '#475569',
    grid: 'rgba(15, 23, 42, 0.06)',
    surface: '#ffffff',
};

Chart.defaults.font.family = 'inherit';
Chart.defaults.font.size = 12;
Chart.defaults.color = PALETTE.textSoft;
Chart.defaults.borderColor = PALETTE.grid;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
Chart.defaults.plugins.legend.labels.boxWidth = 8;
Chart.defaults.plugins.legend.labels.boxHeight = 8;
Chart.defaults.plugins.legend.labels.padding = 16;
Chart.defaults.plugins.legend.labels.font = { size: 12, weight: '500' };
Chart.defaults.plugins.legend.labels.color = PALETTE.text;

const TOOLTIP = {
    backgroundColor: PALETTE.text,
    titleColor: '#ffffff',
    bodyColor: 'rgba(255,255,255,0.85)',
    borderColor: PALETTE.primary,
    borderWidth: 1,
    padding: 12,
    cornerRadius: 8,
    displayColors: true,
    usePointStyle: true,
    boxPadding: 6,
    titleFont: { weight: '600', size: 12 },
    bodyFont: { size: 12 },
};

const DOUGHNUT_PALETTE = [
    PALETTE.primary,
    PALETTE.primarySoft,
    PALETTE.muted,
    PALETTE.mutedSoft,
];

const DAYS_LABELS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

function hexToRgba(hex, alpha) {
    const value = hex.replace('#', '');
    const r = parseInt(value.substring(0, 2), 16);
    const g = parseInt(value.substring(2, 4), 16);
    const b = parseInt(value.substring(4, 6), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.analytics-stats');
    if (!root) {
        return;
    }

    const endpoint = root.dataset.endpoint;
    const fromInput = document.getElementById('stats-from');
    const toInput = document.getElementById('stats-to');
    const form = document.getElementById('stats-range');
    const exportLink = document.getElementById('stats-export');
    const localeButtons = root.querySelectorAll('.stats-locale-chip');
    const loader = root.querySelector('.stats-loader');
    let currentLocale = '';

    function setLoading(active) {
        if (loader) {
            loader.classList.toggle('d-none', !active);
            loader.setAttribute('aria-busy', active ? 'true' : 'false');
        }
        root.classList.toggle('is-loading', active);
    }

    const charts = {
        series: null,
        topPages: null,
        sources: null,
        devices: null,
        heatmap: null,
    };

    function buildUrl(extension) {
        const params = new URLSearchParams({
            from: fromInput.value,
            to: toInput.value,
        });
        if (currentLocale) {
            params.set('locale', currentLocale);
        }
        const base = extension ? endpoint.replace('.json', extension) : endpoint;
        return `${base}?${params.toString()}`;
    }

    function setKpi(name, value) {
        const node = root.querySelector(`[data-kpi="${name}"]`);
        if (node) {
            node.textContent = new Intl.NumberFormat('fr-FR').format(value);
        }
    }

    function destroy(name) {
        if (charts[name]) {
            charts[name].destroy();
            charts[name] = null;
        }
    }

    function renderSeries(series) {
        destroy('series');
        const ctx = document.getElementById('chart-series');
        charts.series = new Chart(ctx, {
            type: 'line',
            data: {
                datasets: [
                    {
                        label: 'Visiteurs',
                        data: series.map(r => ({ x: r.date, y: r.visitors })),
                        borderColor: PALETTE.primary,
                        backgroundColor: hexToRgba(PALETTE.primary, 0.12),
                        pointBackgroundColor: PALETTE.primary,
                        pointBorderColor: PALETTE.surface,
                        pointBorderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                    },
                    {
                        label: 'Sessions',
                        data: series.map(r => ({ x: r.date, y: r.sessions })),
                        borderColor: PALETTE.primarySoft,
                        backgroundColor: 'transparent',
                        pointBackgroundColor: PALETTE.primarySoft,
                        pointBorderColor: PALETTE.surface,
                        pointBorderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        borderWidth: 2,
                        borderDash: [4, 4],
                        tension: 0.35,
                    },
                    {
                        label: 'Pages vues',
                        data: series.map(r => ({ x: r.date, y: r.pageviews })),
                        borderColor: PALETTE.muted,
                        backgroundColor: 'transparent',
                        pointBackgroundColor: PALETTE.muted,
                        pointBorderColor: PALETTE.surface,
                        pointBorderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        borderWidth: 2,
                        tension: 0.35,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: {
                        type: 'time',
                        time: { unit: 'day', tooltipFormat: 'd LLL yyyy' },
                        grid: { display: false },
                        ticks: { color: PALETTE.textSoft },
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: PALETTE.grid, drawTicks: false },
                        ticks: { color: PALETTE.textSoft, padding: 8 },
                    },
                },
                plugins: {
                    legend: { position: 'bottom', align: 'start' },
                    tooltip: TOOLTIP,
                },
            },
        });
    }

    function renderBars(canvasId, breakdown, label, key) {
        destroy(key);
        const ctx = document.getElementById(canvasId);
        charts[key] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: breakdown.map(r => r.label),
                datasets: [{
                    label,
                    data: breakdown.map(r => r.value),
                    backgroundColor: hexToRgba(PALETTE.primary, 0.85),
                    hoverBackgroundColor: PALETTE.primary,
                    borderRadius: 6,
                    borderSkipped: false,
                    barThickness: 14,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: TOOLTIP,
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: PALETTE.grid, drawTicks: false },
                        ticks: { color: PALETTE.textSoft, padding: 6 },
                    },
                    y: {
                        border: { display: false },
                        grid: { display: false },
                        ticks: { color: PALETTE.text, padding: 8 },
                    },
                },
            },
        });
    }

    function renderDevices(devices) {
        destroy('devices');
        const ctx = document.getElementById('chart-devices');
        charts.devices = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: devices.map(r => r.label),
                datasets: [{
                    data: devices.map(r => r.value),
                    backgroundColor: devices.map((_, i) => DOUGHNUT_PALETTE[i % DOUGHNUT_PALETTE.length]),
                    borderColor: PALETTE.surface,
                    borderWidth: 2,
                    hoverOffset: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { position: 'bottom', align: 'center' },
                    tooltip: TOOLTIP,
                },
            },
        });
    }

    function renderCountries(countries) {
        const tbody = document.querySelector('#table-countries tbody');
        if (!tbody) return;
        tbody.innerHTML = '';
        countries.forEach(row => {
            const tr = document.createElement('tr');
            const tdLabel = document.createElement('td');
            tdLabel.textContent = row.label;
            const tdValue = document.createElement('td');
            tdValue.className = 'text-end';
            tdValue.textContent = new Intl.NumberFormat('fr-FR').format(row.value);
            tr.appendChild(tdLabel);
            tr.appendChild(tdValue);
            tbody.appendChild(tr);
        });
    }

    function renderClicks(clicks) {
        const tbody = document.querySelector('#table-clicks tbody');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!clicks || clicks.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 3;
            td.className = 'text-center text-muted py-3';
            td.textContent = 'Aucun clic enregistré sur la période.';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }
        clicks.forEach(row => {
            const tr = document.createElement('tr');
            const tdLabel = document.createElement('td');
            tdLabel.textContent = row.label;
            const tdAction = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = 'badge bg-secondary-subtle text-secondary';
            badge.textContent = row.action;
            tdAction.appendChild(badge);
            const tdValue = document.createElement('td');
            tdValue.className = 'text-end';
            tdValue.textContent = new Intl.NumberFormat('fr-FR').format(row.count);
            tr.appendChild(tdLabel);
            tr.appendChild(tdAction);
            tr.appendChild(tdValue);
            tbody.appendChild(tr);
        });
    }

    function renderHeatmap(heatmap) {
        destroy('heatmap');
        const ctx = document.getElementById('chart-heatmap');
        const max = heatmap.reduce((m, c) => Math.max(m, c.pageviews), 0) || 1;
        const data = heatmap.map(c => ({ x: c.hour, y: DAYS_LABELS[c.dow - 1], v: c.pageviews }));

        charts.heatmap = new Chart(ctx, {
            type: 'matrix',
            data: {
                datasets: [{
                    label: 'Pages vues',
                    data,
                    backgroundColor: ({ raw }) => {
                        if (!raw || !raw.v) return hexToRgba(PALETTE.primary, 0.04);
                        const alpha = 0.18 + 0.72 * (raw.v / max);
                        return hexToRgba(PALETTE.primary, alpha);
                    },
                    borderColor: 'transparent',
                    borderWidth: 0,
                    width: ({ chart }) => (chart.chartArea || {}).width / 24 - 2,
                    height: ({ chart }) => (chart.chartArea || {}).height / 7 - 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...TOOLTIP,
                        callbacks: {
                            title: () => '',
                            label: (ctx) => `${ctx.raw.y} ${ctx.raw.x}h - ${ctx.raw.v} pv`,
                        },
                    },
                },
                layout: {
                    padding: { top: 4, right: 8, bottom: 12, left: 4 },
                },
                scales: {
                    x: {
                        type: 'linear',
                        min: 0,
                        max: 23,
                        offset: true,
                        ticks: { stepSize: 1, color: PALETTE.textSoft, padding: 10 },
                        border: { display: false },
                        grid: { display: false },
                    },
                    y: {
                        type: 'category',
                        labels: DAYS_LABELS,
                        offset: true,
                        border: { display: false },
                        grid: { display: false },
                        ticks: { color: PALETTE.textSoft, padding: 10 },
                    },
                },
            },
        });
    }

    async function refresh() {
        setLoading(true);
        try {
            const response = await fetch(buildUrl(), { credentials: 'same-origin' });
            if (!response.ok) return;
            const data = await response.json();

            setKpi('visitors', data.totals.visitors);
            setKpi('sessions', data.totals.sessions);
            setKpi('pageviews', data.totals.pageviews);

            renderSeries(data.series);
            renderBars('chart-top-pages', data.topPages, 'Pages vues', 'topPages');
            renderBars('chart-sources', data.sources, 'Sessions', 'sources');
            renderDevices(data.devices);
            renderCountries(data.countries);
            renderClicks(data.clicks);
            renderHeatmap(data.heatmap);
        } catch (_) {
            // Stats are non-critical; failure must not break the admin.
        } finally {
            setLoading(false);
        }
    }

    function updateExportHref() {
        exportLink.href = buildUrl('.csv');
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        updateExportHref();
        refresh();
    });

    localeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            localeButtons.forEach((b) => b.classList.remove('active'));
            button.classList.add('active');
            currentLocale = button.dataset.locale || '';
            updateExportHref();
            refresh();
        });
    });

    updateExportHref();
    refresh();
});
