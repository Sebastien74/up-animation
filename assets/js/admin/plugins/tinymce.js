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
                    menubar: false,
                    statusbar: false,
                    height: 400,
                    max_height: 500,
                    language: 'fr_FR', /** https://www.tiny.cloud/get-tiny/language-packages/ */
                    base_url: domain + '/bundles/tinymce/ext/tinymce',
                    language_url: domain + '/js/langs/fr_FR.js',
                    toolbar: toolbar,
                    plugins: 'emoticons link media lists table code searchreplace fullscreen',
                    skin: (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'oxide-dark' : 'oxide'),
                    content_css: (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'default'),
                    font_css: fontsCss,
                    font_family_formats: pluginsData.dataset.fontsFormatEditor,
                    font_size_formats: "8px 10px 12px 14px 16px 17px 18px 22px 26px 32px 36px 48px 60px 72px 96px",
                    color_cols: 4,
                    color_map: colors,
                    content_style: "body { background-color: #292929; color: #adb5bd;} body .sr-only {display: none;}",
                    setup: (tinymceEl) => {

                        const runAccessibility = () => accessibilityFields(tinymceEl, editor);
                        tinymceEl.on('input', runAccessibility);       // pour la frappe
                        tinymceEl.on('NodeChange', runAccessibility);  // pour les modifications structurelles
                        tinymceEl.on('SetContent', runAccessibility);  // lors du chargement initial (collage HTML, chargement AJAX)
                        tinymceEl.on('init', runAccessibility);        // déclenche à l’ouverture
                        tinymceEl.on('LoadContent', runAccessibility); // Bonus : appel initial après le rendu

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

                        tinymceEl.on('paste', () => {
                            setTimeout(() => {
                                setTimeout(() => {
                                    let content = tinymceEl.getContent();
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
                                    // Remet le contenu nettoyé dans l’éditeur
                                    tinymceEl.setContent(content);
                                }, 0);
                            });
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