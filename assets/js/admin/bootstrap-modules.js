/**
 * Module : Back Bootstrap
 * Copyright : 2025
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 */

export function Tooltip() {

    import('./bootstrap/dist/tooltip').then(({default: Tooltip}) => {

        const initOne = (el) => {

            if (el.classList.contains('tooltip-loaded')) return;

            const instance = Tooltip.getOrCreateInstance(el, {
                container: 'body',
                trigger: 'hover focus',
                boundary: 'viewport',
                delay: {show: 80, hide: 40}
            });

            el.addEventListener('shown.bs.tooltip', () => {
                const variant = el.getAttribute('data-tooltip-variant');
                if (!variant) return;
                const tip = instance.tip;
                if (!tip) return;
                tip.setAttribute('data-variant', variant);
            });

            el.classList.add('tooltip-loaded');
        };

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(initOne);

    }).catch(error => console.error(error.message));
}
