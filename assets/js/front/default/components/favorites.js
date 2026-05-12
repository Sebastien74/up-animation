/**
 * Favorites
 *
 * Cookie-backed favorites toggle for product cards.
 * Pure client-side, no HTTP calls.
 *
 * @copyright 2026
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 * @licence under the MIT License (LICENSE.txt)
 */

const COOKIE_NAME = 'up_favorites';
const MAX_FAVORITES = 80;
const COOKIE_DAYS = 365;

function readIds() {
    const match = document.cookie.match(new RegExp('(?:^|; )' + COOKIE_NAME + '=([^;]*)'));
    if (!match) return [];
    try {
        const decoded = JSON.parse(decodeURIComponent(match[1]));
        if (!Array.isArray(decoded)) return [];
        const ids = [];
        for (const value of decoded) {
            const id = parseInt(value, 10);
            if (id > 0 && !ids.includes(id)) ids.push(id);
            if (ids.length >= MAX_FAVORITES) break;
        }
        return ids;
    } catch (e) {
        return [];
    }
}

function writeIds(ids) {
    const value = encodeURIComponent(JSON.stringify(ids));
    const expires = new Date(Date.now() + COOKIE_DAYS * 86400 * 1000).toUTCString();
    document.cookie = `${COOKIE_NAME}=${value}; expires=${expires}; path=/; SameSite=Lax`;
}

export default class Favorites {
    constructor() {
        this.fixedBtn = document.getElementById('favorites-fixed-btn');
        this.countEl = this.fixedBtn ? this.fixedBtn.querySelector('[data-favorites-count]') : null;
        this.bind();
        this.sync(readIds());
    }

    bind() {
        document.addEventListener('click', (event) => {
            const toggle = event.target.closest('.favorite-toggle');
            if (toggle) {
                event.preventDefault();
                event.stopPropagation();
                this.handleToggle(toggle);
            }
        });
        document.addEventListener('favorites:change', (event) => this.sync(event.detail.ids));
    }

    handleToggle(toggle) {
        const id = parseInt(toggle.dataset.productId, 10);
        if (!id) return;

        const ids = readIds();
        const index = ids.indexOf(id);
        if (index === -1) {
            if (ids.length >= MAX_FAVORITES) return;
            ids.push(id);
        } else {
            ids.splice(index, 1);
        }

        writeIds(ids);
        document.dispatchEvent(new CustomEvent('favorites:change', {
            detail: { ids, changedId: id }
        }));
    }

    sync(ids) {
        const set = new Set(ids);
        const toggles = document.querySelectorAll('.favorite-toggle');
        const pendingLabels = [];

        toggles.forEach((el) => {
            const id = parseInt(el.dataset.productId, 10);
            const active = set.has(id);
            el.classList.toggle('is-favorite', active);
            el.setAttribute('aria-pressed', active ? 'true' : 'false');
            const labelAdd = el.dataset.labelAdd;
            const labelRemove = el.dataset.labelRemove;
            if (labelAdd && labelRemove) {
                const label = active ? labelRemove : labelAdd;
                el.setAttribute('aria-label', label);
                el.setAttribute('data-bs-title', label);
                pendingLabels.push({ el, label });
            }
        });

        if (pendingLabels.length > 0) {
            import('../../bootstrap/dist/tooltip').then(({ default: Tooltip }) => {
                pendingLabels.forEach(({ el, label }) => {
                    const instance = Tooltip.getInstance(el);
                    if (instance) {
                        instance.setContent({ '.tooltip-inner': label });
                    }
                });
            }).catch(() => {});
        }

        document.querySelectorAll('[data-favorite-card]').forEach((card) => {
            const id = parseInt(card.dataset.favoriteCard, 10);
            if (id && !set.has(id)) card.remove();
        });

        if (this.fixedBtn) {
            this.fixedBtn.classList.toggle('d-none', ids.length === 0);
        }
        if (this.countEl) {
            this.countEl.textContent = ids.length.toString();
            if (ids.length === 0) {
                this.countEl.setAttribute('hidden', '');
            } else {
                this.countEl.removeAttribute('hidden');
            }
        }

        const emptyState = document.querySelector('.favorites-empty');
        const list = document.querySelector('.favorites-list');
        const actions = document.querySelectorAll('[data-favorites-actions]');
        if (list && emptyState && !list.querySelector('[data-favorite-card]')) {
            list.classList.add('d-none');
            emptyState.classList.remove('d-none');
            actions.forEach((el) => el.classList.add('d-none'));
        }
    }
}
