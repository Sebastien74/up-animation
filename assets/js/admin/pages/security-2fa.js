/**
 * Copy backup codes to clipboard from .code-chip elements.
 * SCSS already shipped via admin-vendor → admin pages/security-2fa.scss.
 */

const COPIED_CLASS = 'is-copied';
const COPIED_RESET_MS = 1600;

async function copyCode(chip) {
    const code = chip.dataset.code;

    if (!code) {
        return;
    }

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(code);
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = code;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }

        chip.classList.add(COPIED_CLASS);
        window.setTimeout(() => chip.classList.remove(COPIED_CLASS), COPIED_RESET_MS);
    } catch (err) {
        // Silent fail on clipboard rejection (browser permission, focus loss).
    }
}

function init() {
    const chips = document.querySelectorAll('#security-2fa-page .code-chip[data-code]');

    if (!chips.length) {
        return;
    }

    chips.forEach((chip) => {
        chip.addEventListener('click', () => copyCode(chip));

        chip.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                copyCode(chip);
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

export default function () {
}
