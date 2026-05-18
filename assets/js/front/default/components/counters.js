/**
 * Counter
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import('../../../../scss/front/default/components/blocks/_counter.scss');

export default function (counters) {

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const start = parseFloat(counter.dataset.counterStart.replace(",", ".")) || 0;
                const endValue = counter.dataset.counterEnd.replace(",", ".");
                const end = parseFloat(endValue);
                const duration = parseInt(counter.dataset.counterTime) || 2000;
                const separator = counter.dataset.counterSeparator !== undefined ? JSON.parse(counter.dataset.counterSeparator) : false;
                let decimals = counter.dataset.counterDecimals ? parseInt(counter.dataset.counterDecimals) : 0;
                if (endValue && /[.,]/.test(endValue)) {
                    const decimalPlaces = endValue.split(/[.,]/)[1];
                    decimals = decimalPlaces ? decimalPlaces.length : decimals;
                }
                animateCounter(counter, start, end, duration, decimals, separator);
                observer.unobserve(counter);
            }
        });
    }, {
        threshold: 0.5
    });

    counters.forEach(counter => {
        observer.observe(counter);
    });

    /**
     * Animate the counter-value from start to end.
     */
    function animateCounter(counter, start, end, duration, decimals, separator) {
        const startTime = performance.now();
        let current = start;
        function update() {
            const elapsed = performance.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            current = start + (end - start) * progress;
            if (progress >= 1) {
                current = end;
                counter.textContent = formatValue(current, decimals, separator);
                return;
            }
            counter.textContent = formatValue(current, decimals, separator);
            requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }

    /**
     * Format the counter-value with French decimals and optional thousands separator.
     */
    function formatValue(value, decimals, separator) {
        if (isNaN(value)) {
            console.error("La valeur n'est pas un nombre valide !");
            return "";
        }
        let formattedValue;
        if (decimals > 0) {
            formattedValue = value.toFixed(decimals);
        } else {
            formattedValue = Math.round(value).toString();
        }
        formattedValue = formattedValue.replace(".", ",");
        if (separator) {
            const parts = formattedValue.split(",");
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, " ");
            formattedValue = parts.join(",");
        }
        return formattedValue;
    }
}