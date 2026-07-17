/**
 * Tinymce
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export function refreshTinymce() {
    let editors = document.querySelectorAll('.tinymce');
    editors.forEach(function (editor) {
        let textareaId = editor.getAttribute('id');
        let tinymceEditor = tinymce.get(textareaId);
        if (tinymceEditor) {
            try {
                tinymceEditor.save();
                /** Close all popups/menus */
                tinymceEditor.execCommand('mceCloseAllPopups');
                /** Force to close toolbar overflow by clicking the button if it's open */
                document.querySelectorAll('.tox-tbtn--opened, .tox-tbtn--enabled[aria-expanded="true"]').forEach(function (button) {
                    button.click();
                });
                /** Handle other open popups/menus that might not be closed by the command */
                document.querySelectorAll('.tox-menu, .tox-popover, .tox-dialog-wrap, .tox-toolbar__overflow').forEach(function (el) {
                    el.style.display = 'none';
                });
                tinymceEditor.focus();
                tinymceEditor.nodeChanged();
            } catch (error) {
                console.log(error);
            }
        }
    });
}

export function accessibilityFields(tinymceEl, editor) {
    let setContent = false;
    let content = tinymceEl.getContent();
    if (content) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(content, "text/html");
        const tables = doc.querySelectorAll("table");
        const tableAlert = editor.parentNode.querySelector('.table-alert');
        if (tableAlert && tables.length > 0) {
            tableAlert.classList.remove('d-none');
        } else if (tableAlert) {
            tableAlert.classList.add('d-none');
        }
        tables.forEach(function (table) {
            // Remove <colgroup> if present (not useful for accessibility)
            const colgroup = table.querySelector("colgroup");
            if (colgroup) {
                colgroup.remove();
                setContent = true;
            }
            const hasCaption = table.querySelector("caption") !== null;
            // Add <caption> if missing and a visual title exists above
            if (!hasCaption) {
                let previous = table.previousElementSibling;
                while (previous && (previous.tagName === 'TABLE' || previous.textContent.trim() === '')) {
                    previous = previous.previousElementSibling;
                }
                if (previous) {
                    const titleText = previous.textContent.trim();
                    const caption = doc.createElement('caption');
                    caption.textContent = titleText;
                    caption.classList.add('sr-only');
                    table.insertBefore(caption, table.firstChild);
                    setContent = true;
                }
            }
            const tbody = table.querySelector("tbody");
            const thead = table.querySelector("thead");
            // If no <thead>, extract first row from <tbody> and convert to <thead>
            if (!thead && tbody && tbody.rows.length > 0) {
                const firstRow = tbody.rows[0];
                const newThead = doc.createElement("thead");
                const newRow = doc.createElement("tr");
                Array.from(firstRow.cells).forEach(cell => {
                    const th = doc.createElement("th");
                    th.setAttribute("scope", "col");
                    th.innerHTML = cell.innerHTML;
                    newRow.appendChild(th);
                });
                newThead.appendChild(newRow);
                table.insertBefore(newThead, tbody);
                tbody.removeChild(firstRow); // Remove the original row from tbody
                setContent = true;
            }
        });
        if (setContent) {
            tinymceEl.setContent(doc.body.innerHTML);
        }
    }
}

export function tinymcePlugin() {

    /** https://github.com/eckinox/tinymce-bundle */
    /** https://www.tiny.cloud/docs/tinymce/6/webcomponent-ref/ */

    let pluginsData = document.getElementById('cms-plugins-data');
    let editors = document.querySelectorAll('.tinymce');

    /**
     * Garde anti-scroll : à l'initialisation, TinyMCE focalise automatiquement un
     * éditeur (le dernier rendu), ce qui amène le navigateur à faire défiler la page
     * pour révéler son iframe — d'où un « scroll vers le milieu » tardif (une fois
     * tous les éditeurs rendus). On neutralise donc la prise de focus automatique
     * tant que l'utilisateur n'a pas interagi : aucun focus => aucun scroll. La garde
     * est levée à la première interaction réelle (l'utilisateur peut alors cliquer
     * dans un éditeur normalement), ou après un court délai de sécurité.
     */
    const focusGuard = { active: true };
    const releaseFocusGuard = () => {
        focusGuard.active = false;
    };
    ['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach((ev) => {
        window.addEventListener(ev, releaseFocusGuard, { once: true, capture: true });
    });
    setTimeout(releaseFocusGuard, 2500);

    let colors = [];
    let colorsData = pluginsData.dataset.colorsEditor;
    if (typeof colorsData != "undefined") {
        let colorsDataExplode = colorsData.split(',');
        for (let i = 0; i < colorsDataExplode.length; i++) {
            let color = colorsDataExplode[i].trim();
            colors.push(color);
        }
    }

    let fontsCss = [];
    let fontsCssData = pluginsData.dataset.fontsCssEditor;
    if (typeof fontsCssData != "undefined") {
        let fontsDataExplode = fontsCssData.split('#');
        for (let i = 0; i < fontsDataExplode.length; i++) {
            if (fontsDataExplode[i].trim()) {
                fontsCss.push(fontsDataExplode[i].trim());
            }
        }
    }

    let toolbar = [
        {name: 'history', items: ['undo', 'redo']},
        {name: 'cleaner', items: ['cleaner']},
        {name: 'paragraph', items: ['paragraph']},
    ];
    if (fontsCss.length > 0) {
        toolbar.push({name: 'styles', items: ['fontsize', 'fontfamily']});
    } else {
        toolbar.push({name: 'styles', items: ['fontsize']});
    }
    toolbar.push(
        {name: 'formatting', items: ['bold', 'italic', 'underline', 'emoticons']},
        {name: 'alignment', items: ['alignleft', 'aligncenter', 'alignright', 'alignjustify']},
        {name: 'color', items: ['forecolor', 'backcolor']},
        {name: 'insert', items: ['link', 'media']},
        {name: 'lists', items: ['numlist', 'bullist']},
        {name: 'table', items: ['table']},
        {name: 'indentation', items: ['outdent', 'indent']},
        {name: 'code', items: ['code', 'fullscreen', 'searchreplace']},
    );

    const url = window.location;
    let domain = (new URL(url)).origin;

    /** Follow the admin theme (fallback to OS preference) so the editor matches dark/light */
    const adminTheme = pluginsData.dataset.adminTheme;
    const isDark = adminTheme ? adminTheme === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
    const contentStyle = isDark
        ? "body { background-color: #14161e; color: #e9ecef; } body .sr-only { display: none; }"
        : "body { background-color: #ffffff; color: #1f2430; } body .sr-only { display: none; }";

    if (typeof tinymce !== 'undefined') {
        editors.forEach(function (editor) {
            let textareaId = editor.getAttribute('id');
            let tinymceEditor = tinymce.get(textareaId);
            if (tinymce.get(textareaId)) {
                tinymceEditor.remove();
            }
            if (textareaId) {
                tinymce.init({
                    selector: '#' + editor.getAttribute('id'),
                    extended_valid_elements: 'script[src|async|defer|type|charset]',
                    /** Encodage des caractères : accents en entités nommées, emojis (et autres
                     *  caractères 4 octets) en entités numériques (&#128197;) pour rester
                     *  compatibles avec des colonnes latin1 qui sinon les corrompent en « ? ». */
                    entity_encoding: 'named+numeric',
                    menubar: false,
                    statusbar: false,
                    height: 400,
                    max_height: 500,
                    language: 'fr_FR', /** https://www.tiny.cloud/get-tiny/language-packages/ */
                    base_url: domain + '/bundles/tinymce/ext/tinymce',
                    language_url: domain + '/js/langs/fr_FR.js',
                    toolbar: toolbar,
                    plugins: 'emoticons link media lists table code searchreplace fullscreen',
                    skin: (isDark ? 'oxide-dark' : 'oxide'),
                    content_css: (isDark ? 'dark' : 'default'),
                    font_css: fontsCss,
                    font_family_formats: pluginsData.dataset.fontsFormatEditor,
                    font_size_formats: "8px 10px 12px 14px 16px 17px 18px 22px 26px 36px 48px 60px 72px 96px",
                    color_cols: 4,
                    color_map: colors,
                    content_style: contentStyle,
                    setup: (tinymceEl) => {

                        /**
                         * Empêche l'éditeur de voler le focus pendant la fenêtre d'init
                         * (cause du scroll auto de la page vers son iframe). On intercepte
                         * editor.focus() ainsi que le focus de l'iframe (body + window),
                         * tant que la garde est active.
                         */
                        const editorNativeFocus = tinymceEl.focus.bind(tinymceEl);
                        tinymceEl.focus = function (skipFocus) {
                            if (focusGuard.active) {
                                return;
                            }
                            return editorNativeFocus(skipFocus);
                        };
                        tinymceEl.on('init', () => {
                            try {
                                const body = tinymceEl.getBody();
                                const win = tinymceEl.getWin();
                                if (body) {
                                    const bodyNativeFocus = body.focus.bind(body);
                                    body.focus = function (opts) {
                                        if (focusGuard.active) {
                                            return;
                                        }
                                        return bodyNativeFocus(opts);
                                    };
                                }
                                if (win) {
                                    const winNativeFocus = win.focus.bind(win);
                                    win.focus = function () {
                                        if (focusGuard.active) {
                                            return;
                                        }
                                        return winNativeFocus();
                                    };
                                }
                            } catch (e) {
                                console.log(e);
                            }
                        });

                        const runAccessibility = () => accessibilityFields(tinymceEl, editor);
                        const closePopups = () => {
                            try {
                                tinymceEl.execCommand('mceCloseAllPopups');
                                /** Force to close toolbar overflow by clicking the button if it's open */
                                document.querySelectorAll('.tox-tbtn--opened, .tox-tbtn--enabled[aria-expanded="true"]').forEach(function (button) {
                                    button.click();
                                });
                                /** Handle other open popups/menus that might not be closed by the command */
                                document.querySelectorAll('.tox-menu, .tox-popover, .tox-dialog-wrap, .tox-toolbar__overflow').forEach(function (el) {
                                    el.style.display = 'none';
                                });
                                tinymceEl.nodeChanged();
                            } catch (e) {}
                        };

                        tinymceEl.on('input', runAccessibility);       // pour la frappe
                        tinymceEl.on('NodeChange', runAccessibility);  // pour les modifications structurelles
                        tinymceEl.on('SetContent', () => {
                            runAccessibility();
                            closePopups();
                        });  // lors du chargement initial (collage HTML, chargement AJAX)
                        tinymceEl.on('CloseWindow', closePopups);
                        tinymceEl.on('init', runAccessibility);        // déclenche à l’ouverture
                        tinymceEl.on('LoadContent', runAccessibility); // Bonus : appel initial après le rendu

                        /**
                         * Filet de sécurité encodage : à l'enregistrement, convertit tout
                         * caractère hors latin1 (emojis astraux > U+FFFF, mais aussi guillemets
                         * typographiques, tirets longs, etc.) en entité HTML numérique (&#128197;).
                         * Sans ça, une colonne DB en latin1 les remplace par « ? » à l'insertion.
                         * Complète entity_encoding qui laisse souvent les caractères astraux bruts.
                         */
                        const encodeNonLatin1 = (str) => str.replace(
                            /[\uD800-\uDBFF][\uDC00-\uDFFF]|[Ā-퟿-￿]/g,
                            (ch) => '&#' + ch.codePointAt(0) + ';'
                        );
                        tinymceEl.on('SaveContent', (e) => {
                            e.content = encodeNonLatin1(e.content);
                        });

                        /** https://www.tiny.cloud/docs/advanced/editor-icon-identifiers/ */
                        tinymceEl.ui.registry.addMenuButton('paragraph', {
                            icon: 'format',
                            fetch: (callback) => {
                                let items = [];
                                ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'].forEach(function (name) {
                                    items.push({
                                        type: 'menuitem',
                                        text: name,
                                        onAction: function () {
                                            tinymceEl.execCommand('FormatBlock', false, name);
                                        }
                                    });
                                });
                                callback(items);
                            }
                        });

                        tinymceEl.ui.registry.addButton('cleaner', {
                            icon: 'sharpen',
                            onAction: function () {
                                /** Get the content from the editor */
                                let content = tinymceEl.getContent();
                                /** Remove HTML and styles */
                                let strippedContent = content.replace(/<[^>]*>/g, '');
                                /** Set the cleaned content back into the editor */
                                tinymceEl.setContent(strippedContent);
                            }
                        });

                        // Nettoyage du contenu collé UNIQUEMENT (fragment collé, pas tout l'éditeur).
                        // PastePreProcess fournit e.content = le seul fragment collé, inséré ensuite
                        // au curseur par TinyMCE : le contenu déjà présent et sa mise en forme sont préservés.
                        tinymceEl.on('PastePreProcess', (e) => {
                            let content = e.content;
                            // Nettoyage du contenu collé
                            content = content.replace(/<!--[\s\S]*?-->/g, "") // Supprime commentaires HTML
                                .replace(/<\/?(span|o:p|st1:|xml|meta|link|font)[^>]*>/g, "") // Supprime balises parasites de Word
                                .replace(/<[^\/>]+>\s*[\r\n]+\s*<\/[^>]+>/g, ""); // Supprime balises contenant uniquement des retours à la ligne
                            // Suppression des balises inutiles
                            content = content.trim()
                                .replace(/^(<p[^>]*>\s*)+/, "<p>")
                                .replace(/(\s*<\/p>)+$/, "</p>")
                                .replace(/<p>\s*(?:&nbsp;|<br\s*\/?>)*\s*<\/p>/gi, "<br>")
                                .replace(/^(<br\s*\/?>\s*)+/i, "")
                                .replace(/(\s*<br\s*\/?>)+$/i, "")
                                .replace(/<h([1-6])[^>]*>([\s\S]*?)<\/h\1>/gi, function (match, tag, innerContent) {
                                    let cleanedText = innerContent.replace(/<\/?[^>]+>/g, ''); // Supprime toutes les balises internes
                                    let newTag = parseInt(tag) === 1 ? 2 : tag;
                                    return `<h${newTag}>${cleanedText}</h${newTag}>`; // Reconstruit la balise propre
                                })
                                .replace(/<([a-zA-Z0-9]+)[^>]*>\s*<\/\1>/gi, "")
                                .replace(/(<br\s*\/?>\s*){2,}/gi, "<br>")
                                .replace(/<br\s*\/?>\s*(<([a-zA-Z0-9]+)[^>]*>)/gi, "$1");
                            // Suppression des attributs
                            content = content.replace(/<(\w+)(?:\s+[^>]*?)?>/g, "<$1>");
                            // Renvoie uniquement le fragment collé nettoyé (inséré au curseur)
                            e.content = content;
                        });
                    }
                });
                let tinymceEditor = tinymce.get(textareaId);
                try {
                    if (tinymceEditor) {
                        tinymceEditor.save();
                    }
                } catch (error) {
                    console.log(error);
                }
            }
        });
    }
}