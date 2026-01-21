/**
 * Counter
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (counters) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {

            if (!entry.isIntersecting) {
                return;
            }

            const counter = entry.target;

            const start = parseFloat((counter.dataset.counterStart || '0').replace(',', '.')) || 0;

            const endValueRaw = (counter.dataset.counterEnd || '').toString();
            const end = parseFloat(endValueRaw.replace(',', '.'));

            // Duration (ms): prefer `data-counter-duration`, fallback to legacy `data-counter-time`.
            const durationRaw = counter.dataset.counterDuration ?? counter.dataset.counterTime;
            const duration = parseInt(durationRaw, 10) || 2000;

            const separator = counter.dataset.counterSeparator !== undefined
                ? JSON.parse(counter.dataset.counterSeparator)
                : false;

            let decimals = counter.dataset.counterDecimals ? parseInt(counter.dataset.counterDecimals, 10) : 0;
            if (endValueRaw && /[.,]/.test(endValueRaw)) {
                const decimalPlaces = endValueRaw.split(/[.,]/)[1];
                decimals = decimalPlaces ? decimalPlaces.length : decimals;
            }

            if (Number.isNaN(end)) {
                observer.unobserve(counter);
                return;
            }

            animateCounter(counter, start, end, duration, decimals, separator);
            observer.unobserve(counter);
        });
    }, {
        threshold: 0.5
    });

    counters.forEach(counter => observer.observe(counter));

    /**
     * Animate counter from start to end within duration.
     */
    function animateCounter(counter, start, end, duration, decimals, separator) {
        const startTime = performance.now();
        /**
         * Update animation frame.
         */
        function update() {
            const elapsed = performance.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const current = start + (end - start) * progress;
            counter.textContent = formatValue(progress >= 1 ? end : current, decimals, separator);
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        requestAnimationFrame(update);
    }

    /**
     * Format a numeric value with decimals, french decimal comma and optional thousands' separator.
     */
    function formatValue(value, decimals, separator) {
        if (Number.isNaN(value)) {
            return '';
        }
        let formattedValue = decimals > 0
            ? value.toFixed(decimals)
            : Math.round(value).toString();
        formattedValue = formattedValue.replace('.', ',');
        if (separator) {
            const parts = formattedValue.split(',');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            formattedValue = parts.join(',');
        }
        return formattedValue;
    }
}