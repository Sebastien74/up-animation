/**
 * Module : Back Bootstrap
 * Copyright : 2026
 * Author : Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * Licensed under MIT (https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
 */

export function Tooltip() {

    if (document.querySelectorAll('[data-bs-toggle="tooltip"]').length === 0) return;

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

export function Popover() {

    if (document.querySelectorAll('[data-bs-toggle="popover"]').length === 0) return;

    import('./bootstrap/dist/popover').then(({default: Popover}) => {
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(popoverTriggerEl => {
            new Popover(popoverTriggerEl);
        });
    }).catch(error => console.error(error.message));
}

export function Collapse() {

    if (document.querySelectorAll('.collapse').length === 0) return;

    import('./bootstrap/dist/collapse').then(({default: Collapse}) => {
        document.querySelectorAll('.collapse').forEach(collapseEl => {
            new Collapse(collapseEl, {
                toggle: false
            });
        });
    }).catch(error => console.error(error.message));
}

export function Tab() {

    if (document.querySelectorAll('[data-bs-toggle="tab"], [data-bs-toggle="pill"], [data-bs-toggle="list"]').length === 0) return;

    // Importing the module registers Bootstrap's native data-api click delegation,
    // which activates tabs without the broken per-button `new Tab()` interop.
    import('./bootstrap/dist/tab').catch(error => console.error(error.message));
}

export function Modal() {

    if (document.querySelectorAll('.modal').length === 0) return;

    import('./bootstrap/dist/modal').then(({default: Modal}) => {
        document.querySelectorAll('.modal').forEach(modalEl => {
            new Modal(modalEl, {
                backdrop: 'static',
                keyboard: false
            });
        });
    }).catch(error => console.error(error.message));
}
