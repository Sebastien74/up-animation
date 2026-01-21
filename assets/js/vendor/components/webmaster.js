import "../../../scss/vendor/components/_webmaster.scss";

/**
 * Webmaster toolbox
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import Tooltip from '../../../js/front/bootstrap/dist/tooltip';

export default function (webmasterBox) {

    let body = document.body;

    /** Hide tooltip */
    let hideTooltip = function () {
        document.querySelectorAll('.tooltip').forEach(tooltip => {
            tooltip.remove();
        });
    }

    /** Dropdown button event */
    let dropdownEvents = function () {
        let content = document.getElementById('content-page');
        let alreadyHaveTooltip = content ? content.querySelector('[data-bs-toggle="tooltip"]') : false;
        if (!alreadyHaveTooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tooltip => {
                new Tooltip(tooltip);
            });
        }
        let box = document.getElementById('webmaster-box-wrapper');
        if (box) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]:not(.tooltip-loaded)').forEach(tooltip => {
                new Tooltip(tooltip);
                tooltip.classList.add('tooltip-loaded');
            });
            let alreadyHaveDropdown = document.querySelectorAll('.dropdown-toggle:not(#webmaster-box-dropdown)').length > 0;
            let dropdownEl = document.getElementById("webmaster-box-dropdown");
            if (!alreadyHaveDropdown && dropdownEl && !dropdownEl.classList.contains('loaded')) {
                dropdownEl.classList.add('loaded');
                import('../../../js/front/bootstrap/dist/dropdown').then(({default: Dropdown}) => {
                    const dropdown = new Dropdown(dropdownEl, {
                        popperConfig(defaultBsPopperConfig) {
                        }
                    });
                    dropdownEl.addEventListener("mouseenter", function () {
                        if (!dropdownEl.classList.contains('show')) {
                            dropdown.show();
                            hideTooltip();
                        }
                    });
                }).catch(error => console.error(error.message));
            } else {
                dropdownEl.addEventListener("mouseenter", function () {
                    if (!dropdownEl.classList.contains('show')) {
                        dropdownEl.click();
                        hideTooltip();
                    }
                });
            }
        }
    }
    dropdownEvents();

    /** Button edition */
    let webmasterBtn = document.getElementById("webmaster-edit-btn");
    if (webmasterBtn) {
        webmasterBtn.addEventListener("click", function (event) {
            body.classList.toggle('editor');
            event.preventDefault();
        }, false);
    }

    import("../../security/switcher").then(({default: usersSwitcher}) => {
        new usersSwitcher();
    }).catch(error => console.error(error.message));

    /** Internal modal alert */
    let modalEl = document.getElementById('internal-error-modal');
    if (modalEl) {
        import('../../../js/front/bootstrap/dist/modal').then(({default: Modal}) => {
            let modal = new Modal(document.getElementById('internal-error-modal'), {
                backdrop: false,
            })
            modal.show();
        }).catch(error => console.error(error.message));
    }

    if (webmasterBox) {
        webmasterBox.classList.remove('d-none');
    }
}