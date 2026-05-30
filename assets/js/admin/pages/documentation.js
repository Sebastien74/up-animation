// Documentation page enhancements: section TOC with scroll-spy, heading anchors,
// code copy buttons, and lazy Mermaid rendering. Loaded only on the standalone
// documentation pages (the dashboard has no [data-doc-content]).

const FONT_FAMILY = "'Poppins', sans-serif";

// Palettes tuned to match the documentation chrome (see dark.scss / light.scss).
const THEME_VARIABLES = {
    dark: {
        darkMode: true,
        fontFamily: FONT_FAMILY,
        fontSize: '15px',
        background: '#14161e',
        mainBkg: '#242a42',
        primaryColor: '#242a42',
        primaryBorderColor: '#8b6fd6',
        primaryTextColor: '#eef0f6',
        secondaryColor: '#2b3150',
        secondaryBorderColor: '#3a3f52',
        secondaryTextColor: '#eef0f6',
        tertiaryColor: '#1b1f2b',
        tertiaryBorderColor: '#2a2f3e',
        tertiaryTextColor: '#c7ccd9',
        lineColor: '#8b6fd6',
        textColor: '#eef0f6',
        nodeBorder: '#8b6fd6',
        classText: '#eef0f6',
        attributeBackgroundColorOdd: '#171a23',
        attributeBackgroundColorEven: '#1d2130',
        noteBkgColor: '#242a42',
        noteTextColor: '#eef0f6',
        noteBorderColor: '#8b6fd6',
    },
    light: {
        darkMode: false,
        fontFamily: FONT_FAMILY,
        fontSize: '15px',
        background: '#fffdf8',
        mainBkg: '#faeede',
        primaryColor: '#faeede',
        primaryBorderColor: '#c9772f',
        primaryTextColor: '#2b2b2f',
        secondaryColor: '#f3e7d4',
        secondaryBorderColor: '#d9d0bf',
        secondaryTextColor: '#2b2b2f',
        tertiaryColor: '#fffdf8',
        tertiaryBorderColor: '#e4dccd',
        tertiaryTextColor: '#4a4a52',
        lineColor: '#c9772f',
        textColor: '#2b2b2f',
        nodeBorder: '#c9772f',
        classText: '#2b2b2f',
        attributeBackgroundColorOdd: '#fffdf8',
        attributeBackgroundColorEven: '#f6efe2',
        noteBkgColor: '#faeede',
        noteTextColor: '#2b2b2f',
        noteBorderColor: '#c9772f',
    },
};

const THEME_CSS = `
    .er.entityBox, .node rect, .node polygon, .classGroup rect { rx: 8px; ry: 8px; }
    .er.entityLabel text, .nodeLabel, .edgeLabel, .classTitle { font-weight: 500; }
    .relationshipLine, .edgePath .path { stroke-width: 1.4px; }
    .er.relationshipLabel, .edgeLabel { font-size: 12px; }
`;

const slugify = (text) =>
    text
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '') || 'section';

// Assign unique ids to the headings so anchors and the TOC can target them.
const assignIds = (headings) => {
    const used = new Set();
    headings.forEach((heading) => {
        let base = heading.id || slugify(heading.textContent);
        let id = base;
        let n = 2;
        while (used.has(id)) {
            id = `${base}-${n}`;
            n += 1;
        }
        used.add(id);
        heading.id = id;
    });
};

// Build the right-hand "on this page" navigation with a scroll-spy active state.
const buildToc = (headings) => {
    const aside = document.querySelector('[data-doc-toc]');
    const nav = aside?.querySelector('.doc-toc-nav');
    if (!nav || headings.length < 2) {
        return;
    }

    const links = new Map();
    headings.forEach((heading) => {
        const link = document.createElement('a');
        link.className = `doc-toc-link doc-toc-${heading.tagName.toLowerCase()}`;
        link.href = `#${heading.id}`;
        link.textContent = heading.textContent.trim();
        nav.appendChild(link);
        links.set(heading.id, link);
    });

    aside.hidden = false;

    const setActive = (id) => {
        links.forEach((link) => link.classList.remove('active'));
        links.get(id)?.classList.add('active');
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    setActive(entry.target.id);
                }
            });
        },
        { rootMargin: '-90px 0px -72% 0px', threshold: 0 },
    );
    headings.forEach((heading) => observer.observe(heading));
};

// Append a discreet anchor link to each heading (revealed on hover via CSS).
const addHeadingAnchors = (headings) => {
    headings.forEach((heading) => {
        const anchor = document.createElement('a');
        anchor.className = 'doc-anchor';
        anchor.href = `#${heading.id}`;
        anchor.setAttribute('aria-label', 'Lien vers cette section');
        anchor.textContent = '#';
        heading.appendChild(anchor);
    });
};

// Wrap each code block and add a copy-to-clipboard button.
const enhanceCodeBlocks = (content) => {
    content.querySelectorAll('pre').forEach((pre) => {
        if (pre.closest('.doc-codeblock')) {
            return;
        }
        const wrap = document.createElement('div');
        wrap.className = 'doc-codeblock';
        pre.parentNode.insertBefore(wrap, pre);
        wrap.appendChild(pre);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'doc-copy';
        button.textContent = 'Copier';
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(pre.innerText);
                button.textContent = 'Copié';
                button.classList.add('is-done');
                window.setTimeout(() => {
                    button.textContent = 'Copier';
                    button.classList.remove('is-done');
                }, 1500);
            } catch (error) {
                console.error('Clipboard copy failed', error);
            }
        });
        wrap.appendChild(button);
    });
};

// Mermaid is heavy: imported lazily and only when a diagram is present.
const renderMermaid = async (content) => {
    const blocks = content.querySelectorAll('code.language-mermaid');
    if (!blocks.length) {
        return;
    }

    const { default: mermaid } = await import(/* webpackChunkName: "mermaid" */ 'mermaid');
    const isDark = document.body.classList.contains('doc-theme-dark');
    mermaid.initialize({
        startOnLoad: false,
        securityLevel: 'strict',
        theme: 'base',
        themeVariables: isDark ? THEME_VARIABLES.dark : THEME_VARIABLES.light,
        themeCSS: THEME_CSS,
        er: { useMaxWidth: false, entityPadding: 14, diagramPadding: 16 },
        class: { useMaxWidth: false, padding: 12 },
        flowchart: { useMaxWidth: false, curve: 'basis', padding: 16 },
    });

    let index = 0;
    for (const code of blocks) {
        const host = code.closest('pre') || code;
        const source = code.textContent.trim();
        const id = `doc-mermaid-${(index += 1)}`;
        try {
            const { svg } = await mermaid.render(id, source);
            const figure = document.createElement('div');
            figure.className = 'mermaid-diagram';
            figure.innerHTML = svg;
            host.replaceWith(figure);
        } catch (error) {
            host.classList.add('mermaid-error');
            console.error(`Mermaid render failed for ${id}`, error);
        }
    }
};

// Command palette (Ctrl/Cmd+K): full-screen search over the documentation pages,
// with full keyboard navigation. Available on every documentation page.
const initCommandPalette = () => {
    const overlay = document.querySelector('[data-doc-cmdk]');
    if (!overlay) {
        return;
    }
    const input = overlay.querySelector('[data-doc-cmdk-input]');
    const list = overlay.querySelector('[data-doc-cmdk-list]');
    const empty = overlay.querySelector('[data-doc-cmdk-empty]');

    // Build the index from the sidebar navigation (title + url).
    const items = Array.from(document.querySelectorAll('.doc-nav .doc-nav-link')).map((link) => ({
        title: link.textContent.trim(),
        href: link.getAttribute('href'),
    }));

    let results = [];
    let active = 0;

    const highlight = () => {
        Array.from(list.children).forEach((el, i) => el.classList.toggle('active', i === active));
        list.children[active]?.scrollIntoView({ block: 'nearest' });
    };

    const render = () => {
        list.textContent = '';
        results.forEach((item, i) => {
            const li = document.createElement('li');
            li.className = 'doc-cmdk-item';
            const icon = document.createElement('i');
            icon.className = 'icm-file-alt';
            const label = document.createElement('span');
            label.textContent = item.title;
            li.append(icon, label);
            li.addEventListener('click', () => { window.location.href = item.href; });
            li.addEventListener('mousemove', () => { active = i; highlight(); });
            list.appendChild(li);
        });
        empty.hidden = results.length !== 0;
        active = 0;
        highlight();
    };

    const filter = () => {
        const query = input.value.trim().toLowerCase();
        results = query ? items.filter((it) => it.title.toLowerCase().includes(query)) : items.slice();
        render();
    };

    const open = () => {
        overlay.hidden = false;
        document.body.classList.add('doc-cmdk-open');
        input.value = '';
        filter();
        input.focus();
    };

    const close = () => {
        overlay.hidden = true;
        document.body.classList.remove('doc-cmdk-open');
    };

    input.addEventListener('input', filter);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            active = Math.min(active + 1, results.length - 1);
            highlight();
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            active = Math.max(active - 1, 0);
            highlight();
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (results[active]) {
                window.location.href = results[active].href;
            }
        }
    });

    document.querySelectorAll('[data-doc-cmdk-open]').forEach((btn) => btn.addEventListener('click', open));
    overlay.querySelectorAll('[data-doc-cmdk-close]').forEach((el) => el.addEventListener('click', close));
    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            overlay.hidden ? open() : close();
        } else if (event.key === 'Escape' && !overlay.hidden) {
            close();
        }
    });
};

// Portal theme switcher: persists the choice in a cookie read server-side to load
// the matching CSS entrypoint. Scoped to the documentation portal, not the admin theme.
const initThemeSwitch = () => {
    document.querySelectorAll('[data-doc-theme]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.classList.contains('active')) {
                return;
            }
            const value = btn.dataset.docTheme === 'light' ? 'light' : 'dark';
            document.cookie = `doc_theme=${value}; path=/; max-age=31536000; samesite=lax`;
            window.location.reload();
        });
    });
};

initCommandPalette();
initThemeSwitch();

const content = document.querySelector('[data-doc-content]');
if (content) {
    const headings = Array.from(content.querySelectorAll('h2, h3'));
    assignIds(headings);
    buildToc(headings);
    addHeadingAnchors(headings);
    // Mermaid first (it replaces <pre>), then wrap the remaining real code blocks.
    renderMermaid(content).then(() => enhanceCodeBlocks(content));
}
