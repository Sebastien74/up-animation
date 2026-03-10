import 'select2/dist/js/select2.full.min';

/**
 * Selects2
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (selectId = null, containerId = null) {

    if (document.querySelector('.select-2') || document.querySelector('select')) {
        import('../../../scss/vendor/components/_select2.scss');
    }

    let html = document.querySelector('html');
    let language = html ? html.getAttribute('lang') : 'en';

    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
        jQuery.fn.select2.amd.define('select2/i18n/' + language, [], require("select2/src/js/select2/i18n/" + language));
    }

    /**
     *  Set by element ID
     */
    if (selectId && typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
        let selectById = document.getElementById(selectId);
        if (selectById) {
            let $selectById = jQuery(selectById);
            $selectById.select2();
            if (containerId) {
                $selectById.on('select2:open', function () {
                    let openContainer = document.querySelector('span.select2-container--open');
                    if (openContainer) {
                        openContainer.setAttribute('id', containerId);
                    }
                });
            }
        }
    }

    /**
     *  To add custom class to dropdown
     */
    function dropdownClass(select) {
        let dropdownClassName = select.dataset.dropdownClass ? select.dataset.dropdownClass : 'select2-dropdown-container';
        let dropdownBelow = document.querySelector('body .select2-dropdown--below');
        if (dropdownBelow) {
            let dropdown = dropdownBelow.parentElement;
            if (dropdown && !dropdown.classList.contains(dropdownClassName)) {
                dropdown.classList.add(dropdownClassName);
            }
        }
    }

    /**
     *  Selects2 basic
     */
    let selects2Update = function () {

        if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
            return;
        }

        /** In visible DOM */
        let selects = document.querySelectorAll('body .select-2');

        selects.forEach(function (select) {
            generateSerial(select);
            let group = select.closest('.select2-group');
            if (!select.classList.contains('select2-active') && !select.classList.contains('select-icons')) {
                let allowClear = group && group.classList.contains('allow-clear');
                jQuery(select).select2({
                    allowClear: allowClear,
                    language: language,
                    dropdownParent: jQuery(select).parent(),
                    minimumResultsForSearch: select.classList.contains('disable-search') ? Infinity : false /** Hide search box */
                });
                jQuery(select).on('select2:open', function (e) {
                    dropdownClass(select);
                });
                select.classList.add('select2-active');
                if (select.value && !select.classList.contains('selected')) {
                    if (group) group.classList.add('selected');
                }
            }
            select.addEventListener("change", function (e) {
                const removeCardsBtn = document.querySelector('.remove-cards');
                if (removeCardsBtn) {
                    const inputElement = removeCardsBtn.parentNode.querySelector('input');
                    if (inputElement) {
                        const startCatalogsIds = inputElement.dataset.values;
                        try {
                            const parsedIds = JSON.parse(startCatalogsIds).map(Number); // Convertir en nombres
                            const selectedValues = Array.from(e.target.options)
                                .filter(option => option.selected)
                                .map(option => Number(option.value)); // Convertir en nombres
                            // Vérifie si les tableaux sont identiques (mêmes valeurs, ordre non pris en compte)
                            const areSame = parsedIds.length === selectedValues.length &&
                                parsedIds.every(id => selectedValues.includes(id));
                            // Cache ou affiche le bouton
                            if (areSame) {
                                removeCardsBtn.classList.add('d-none'); // Cache si identiques
                            } else {
                                removeCardsBtn.classList.remove('d-none'); // Affiche si différents
                            }
                        } catch (error) {
                            console.error("Erreur de parsing JSON:", error);
                        }
                    }
                }
                if (this.value) {
                    if (group) group.classList.add('selected');
                } else {
                    if (group) group.classList.remove('selected');
                }
            });
        });

        /** In modal */
        let modals = document.querySelectorAll('body .modal');
        modals.forEach(function (modalEl) {
            let selectsInModal = modalEl.querySelectorAll('.select-2');
            if (selectsInModal.length > 0) {
                modalEl.addEventListener('show.bs.modal', function (e) {
                    selectsInModal.forEach(function (select) {
                        if (!select.classList.contains('select2-active') && !select.classList.contains('select-icons')) {
                            jQuery(select).select2({
                                language: language,
                                dropdownParent: jQuery(modalEl),
                                minimumResultsForSearch: select.classList.contains('disable-search') ? Infinity : false /** Hide search box */
                            });
                            jQuery(select).on('select2:open', function (e) {
                                dropdownClass(select);
                            });
                            select.classList.add('select2-active');
                        }
                    });
                });
            }
        });
    };

    /**
     *  Selects2 icons
     */
    let selectsIconUpdate = function () {

        if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
            return;
        }

        /** In visible DOM */
        let selectsIcon = document.querySelectorAll('body .select-icons');
        selectsIcon.forEach(function (select) {
            generateSerial(select);
            if (!select.classList.contains('select2-active')) {
                jQuery(select).select2({
                    language: language,
                    templateResult: iconFormat,
                    dropdownParent: jQuery(select).parent(),
                    minimumResultsForSearch: select.classList.contains('disable-search') ? Infinity : false,
                    /** Hide search box */
                    templateSelection: iconFormat,
                    escapeMarkup: function (m) {
                        return m;
                    }
                });
                jQuery(select).on('select2:open', function (e) {
                    dropdownClass(select);
                });
                select.addEventListener("change", function (e) {
                    let group = select.closest('.form-floating');
                    if (this.value) {
                        if (group) group.classList.add('selected');
                    } else {
                        if (group) group.classList.remove('selected');
                    }
                });
                // select.classList.add('select2-active');
            }
        });

        /** In modal */
        let modals = document.querySelectorAll('body .modal');
        modals.forEach(function (modalEl) {
            let selectsIconInModal = modalEl.querySelectorAll('.select-icons');
            if (selectsIconInModal.length > 0) {
                modalEl.addEventListener('shown.bs.modal', function (e) {
                    selectsIconInModal.forEach(function (select) {
                        generateSerial(select);
                        if (!select.classList.contains('select2-active')) {
                            jQuery(select).select2({
                                language: language,
                                dropdownParent: jQuery(modalEl),
                                templateResult: iconFormat,
                                minimumResultsForSearch: select.classList.contains('disable-search') ? Infinity : false,
                                /** Hide search box */
                                templateSelection: iconFormat,
                                escapeMarkup: function (m) {
                                    return m;
                                }
                            });
                            jQuery(select).on('select2:open', function (e) {
                                dropdownClass(select);
                            });
                            select.classList.add('select2-active');
                        }
                    });
                });
            }
        });

        /** Format icon */
        function iconFormat(icon) {

            if (!icon.id) {
                return icon.text;
            }

            let element = icon.element;
            if (!element) {
                return icon.text;
            }

            if (typeof element.dataset.fz !== 'undefined') {
                return "<span class='fz-" + element.dataset.fz + "'>" + icon.text + "</span>";
            } else if (typeof element.dataset.fw !== 'undefined') {
                return "<span class='fw-" + element.dataset.fw + "'>" + icon.text + "</span>";
            } else if (typeof element.dataset.ff !== 'undefined') {
                return "<span class='ff-" + element.dataset.ff + "'>" + icon.text + "</span>";
            } else if (typeof element.dataset.background !== 'undefined') {
                return "<span class='select-2-background-wrap'><i class='select-2-background' style='background: url(" + element.dataset.background + ");'></i></span>";
            } else if (typeof element.dataset.color !== 'undefined') {
                let color = element.dataset.color;
                return "<span class='color-wrapper me-3'><span class='color " + element.dataset.class + "' style='background-color:" + color + "; border: 1px solid " + color + ";'></span></span>" + icon.text;
            } else if (typeof element.dataset.image !== 'undefined' && typeof element.dataset.text !== 'undefined') {
                let width = typeof element.dataset.width !== 'undefined' ? element.dataset.width : 'auto';
                let height = typeof element.dataset.height !== 'undefined' ? element.dataset.height : 'auto';
                let classname = typeof element.dataset.class !== 'undefined' ? element.dataset.class : '';
                return "<img data-src='" + element.dataset.image + "' class='img-fluid img-icon lazy-load me-2 " + classname + "' width='" + width + "' height='" + height + "'/>" + icon.text;
            } else if (typeof element.dataset.svg !== 'undefined' && typeof element.dataset.text !== 'undefined') {
                return element.dataset.svg + icon.text;
            } else if (typeof element.dataset.image !== 'undefined') {
                return "<img data-src='" + element.dataset.image + "' class='img-fluid img-icon lazy-load' />";
            } else if (typeof element.dataset.icon !== 'undefined') {
                return "<i class='" + element.dataset.icon + "'></i>" + icon.text;
            } else {
                return icon.text;
            }
        }
    };

    function generateSerial(el) {

        let chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
        let stringLength = 10;
        let randomString = '';

        for (let x = 0; x < stringLength; x++) {
            let letterOrNumber = Math.floor(Math.random() * 2);
            if (letterOrNumber === 0) {
                let newNum = Math.floor(Math.random() * 9);
                randomString += newNum;
            } else {
                let rnum = Math.floor(Math.random() * chars.length);
                randomString += chars.substring(rnum, rnum + 1);
            }
        }

        let id = el.getAttribute('id');
        if (!id || id === "false") {
            el.setAttribute('id', randomString);
            let group = el.closest('.form-group');
            if (group) {
                let label = group.querySelector('label');
                if (label) {
                    label.setAttribute('for', randomString);
                }
            }
        }
    }

    selects2Update();
    selectsIconUpdate();
};