/**
 * Sidebar
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
document.querySelectorAll('.sidebar-nav').forEach(sidebar => {
    sidebar.querySelectorAll('.as-arrow').forEach(el => {
        el.addEventListener('click', () => {

            const parent = el.parentNode;
            const collapse = parent.querySelector('.collapse');
            const isActive = el.classList.contains('active');

            const isBigger = el.classList.contains('bigger');

            sidebar.querySelectorAll('.as-arrow').forEach(arrow => {
                const arrowIsBigger = arrow.classList.contains('bigger');
                if (isBigger === arrowIsBigger) {
                    arrow.classList.remove('active');
                }
            });

            sidebar.querySelectorAll('.collapse').forEach(item => {
                const itemParentArrow = item.parentNode.querySelector('.as-arrow');
                const itemIsBigger = itemParentArrow && itemParentArrow.classList.contains('bigger');
                if (isBigger === itemIsBigger) {
                    item.classList.remove('show');
                    item.classList.remove('in');
                }
            });

            if (!isActive) {
                el.classList.add('active');
                if (collapse && el.classList.contains('bigger')) {
                    collapse.classList.add('in');
                } else if (collapse) {
                    collapse.classList.add('show');
                }
            }
        });
    });
});

document.querySelectorAll('.open-sidebar').forEach(el => {
    el.onclick = function () {
        const sidebar = document.querySelector(el.dataset.target);
        const isOpen = sidebar.classList.contains('open');
        if (!isOpen) {
            document.querySelectorAll('.left-sidebar, .right-sidebar').forEach(item => {
                item.classList.remove('open');
            });
            document.querySelectorAll('.open-sidebar i').forEach(icon => {
                if (icon.classList.contains('icm-times')) {
                    icon.classList.add('d-none');
                } else {
                    icon.classList.remove('d-none');
                }
            });
        }
        sidebar.classList.toggle('open');
        el.querySelectorAll('i').forEach(icon => {
            icon.classList.toggle('d-none');
        });
    }
});

// Sidebar filter — match nav items by text content
(() => {
    const sidebar = document.querySelector('.left-sidebar');
    const input = document.getElementById('sidebar-search-input');
    if (!sidebar || !input) {
        return;
    }

    const field = input.closest('.sidebar-search-field');
    const clearBtn = field ? field.querySelector('.sidebar-search-clear') : null;
    const navList = sidebar.querySelector('#sidebar-nav');
    if (!navList) {
        return;
    }

    const leafItems = navList.querySelectorAll('li:not(.in-build-info)');
    const sectionItems = navList.querySelectorAll(':scope > li');

    const openClassFor = (collapse) => {
        const parentLi = collapse.parentElement;
        const arrow = parentLi ? parentLi.querySelector(':scope > a.as-arrow') : null;
        return arrow && arrow.classList.contains('bigger') ? 'in' : 'show';
    };

    const restoreCollapses = () => {
        sidebar.querySelectorAll('.collapse.filter-expanded').forEach(collapse => {
            collapse.classList.remove('in', 'show', 'filter-expanded');
            const parentLi = collapse.parentElement;
            const arrow = parentLi ? parentLi.querySelector(':scope > a.as-arrow') : null;
            if (arrow && arrow.classList.contains('filter-expanded-arrow')) {
                arrow.classList.remove('active', 'filter-expanded-arrow');
            }
        });
    };

    const applyCollapseExpansion = () => {
        sidebar.querySelectorAll('.collapse').forEach(collapse => {
            const hasMatch = collapse.querySelector(':scope li.is-search-match') !== null;
            const openClass = openClassFor(collapse);
            const wasManuallyOpen = collapse.classList.contains(openClass) && !collapse.classList.contains('filter-expanded');
            const parentLi = collapse.parentElement;
            const arrow = parentLi ? parentLi.querySelector(':scope > a.as-arrow') : null;

            if (hasMatch) {
                if (!collapse.classList.contains(openClass)) {
                    collapse.classList.add(openClass, 'filter-expanded');
                    if (arrow && !arrow.classList.contains('active')) {
                        arrow.classList.add('active', 'filter-expanded-arrow');
                    }
                }
            } else if (!wasManuallyOpen) {
                collapse.classList.remove(openClass);
                if (collapse.classList.contains('filter-expanded')) {
                    collapse.classList.remove('filter-expanded');
                }
                if (arrow && arrow.classList.contains('filter-expanded-arrow')) {
                    arrow.classList.remove('active', 'filter-expanded-arrow');
                }
            }
        });
    };

    const isLeafCollapse = (collapse) => !collapse.querySelector(':scope .collapse');

    const apply = (raw) => {
        const query = raw.trim().toLowerCase();
        const isFiltering = query.length > 0;

        sidebar.classList.toggle('is-filtering', isFiltering);
        if (field) {
            field.classList.toggle('is-filtering', isFiltering);
        }

        if (!isFiltering) {
            leafItems.forEach(li => li.classList.remove('is-hidden', 'is-search-match'));
            sectionItems.forEach(li => li.classList.remove('is-hidden', 'is-search-match'));
            restoreCollapses();
            return;
        }

        let anyVisible = false;

        sectionItems.forEach(section => {
            const allLi = Array.from(section.querySelectorAll('li'));
            const reversedLi = allLi.slice().reverse();

            // Pass 1 — match propagation (bottom-up : children resolved before parents).
            reversedLi.forEach(li => {
                const link = li.querySelector(':scope > a');
                if (!link) {
                    li.classList.remove('is-search-match');
                    return;
                }
                const label = (link.textContent || '').trim().toLowerCase();
                const ownMatch = label.includes(query);
                const descendantMatch = li.querySelector(':scope li.is-search-match') !== null;
                li.classList.toggle('is-search-match', ownMatch || descendantMatch);
            });

            // Pass 2 — visibility. Items inside a leaf .collapse (deepest level) stay
            // visible regardless of match : only their color shifts via .is-search-match.
            // Containers / sections : hidden when they have no match.
            allLi.forEach(li => {
                const parent = li.parentElement;
                const insideLeafCollapse = parent
                    && parent.classList.contains('collapse')
                    && isLeafCollapse(parent);

                if (insideLeafCollapse) {
                    li.classList.remove('is-hidden');
                } else {
                    li.classList.toggle('is-hidden', !li.classList.contains('is-search-match'));
                }
            });

            const sectionLink = section.querySelector(':scope > a');
            const sectionLabel = sectionLink ? (sectionLink.textContent || '').trim().toLowerCase() : '';
            const sectionOwnMatch = sectionLabel.includes(query);
            const sectionHasInternalMatch = section.querySelector(':scope li.is-search-match') !== null;
            const visible = sectionOwnMatch || sectionHasInternalMatch;
            section.classList.toggle('is-hidden', !visible);
            section.classList.toggle('is-search-match', visible && (sectionOwnMatch || sectionHasInternalMatch));
            if (visible) {
                anyVisible = true;
            }
        });

        applyCollapseExpansion();

        const empty = sidebar.querySelector('.sidebar-search-empty');
        if (empty) {
            empty.style.display = anyVisible ? 'none' : 'block';
        }
    };

    input.addEventListener('input', e => apply(e.target.value));

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            input.value = '';
            apply('');
            input.focus();
        });
    }

    input.addEventListener('keydown', e => {
        if (e.key === 'Escape' && input.value !== '') {
            input.value = '';
            apply('');
        }
    });
})();

// Translation submenu filter — scoped to each .translation-search-wrap
document.querySelectorAll('.translation-search-wrap').forEach(wrap => {
    const field = wrap.querySelector('.sidebar-search-field');
    const input = wrap.querySelector('.translation-search-input');
    const clearBtn = wrap.querySelector('.translation-search-clear');
    const list = wrap.parentElement;
    if (!input || !list) {
        return;
    }

    const items = list.querySelectorAll(':scope > .translation-domain-item');

    const apply = raw => {
        const query = raw.trim().toLowerCase();
        const isFiltering = query.length > 0;

        if (field) {
            field.classList.toggle('is-filtering', isFiltering);
        }

        let anyVisible = false;

        items.forEach(li => {
            const link = li.querySelector(':scope > a');
            const label = link ? (link.textContent || '').trim().toLowerCase() : '';
            const match = !isFiltering || label.includes(query);
            li.classList.toggle('is-hidden', !match);
            if (match) {
                anyVisible = true;
            }
        });

        wrap.classList.toggle('is-empty', isFiltering && !anyVisible);
    };

    input.addEventListener('input', e => {
        e.stopPropagation();
        apply(e.target.value);
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            input.value = '';
            apply('');
            input.focus();
        });
    }

    input.addEventListener('keydown', e => {
        if (e.key === 'Escape' && input.value !== '') {
            input.value = '';
            apply('');
        }
    });

    // Prevent the surrounding sidebar collapse trigger from toggling
    // when interacting with the search field
    wrap.addEventListener('click', e => e.stopPropagation());
});