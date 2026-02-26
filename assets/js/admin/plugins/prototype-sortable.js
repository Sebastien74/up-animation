/**
 * Prototypes sortable
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let setPositions = function (elId) {

        let progress = 0
        let element = document.getElementById(elId)
        let elements = element.querySelectorAll('.handle-item-prototype')
        let progressBarCard = document.querySelector('.progress-card')
        let progressBar = document.querySelector('.position-progress-bar')

        if (progressBarCard) {
            let progressBarCardContainer = progressBarCard.closest(".progress-card-container")[0]
            let elementToScroll = progressBarCardContainer != null && typeof progressBarCardContainer != 'undefined' ? progressBarCardContainer : progressBarCard
            progressBarCard.classList.remove('d-none')
            window.scrollTo({
                top: (elementToScroll.getBoundingClientRect().top + window.scrollY) - 20,
                behavior: 'smooth'
            })
        }

        let setPosition = function () {
            let handle = element.querySelector('.handle-item-prototype:not(.generate)')
            let path = handle.dataset.path
            let url = path + '?ajax=true&position=';
            if (path.indexOf('?') > -1) {
                url = path + '&ajax=true'
            }
            let xHttp = new XMLHttpRequest()
            xHttp.open("GET", url + '&position=' + handle.dataset.position, true)
            xHttp.send()
            xHttp.onload = function (e) {
                if (this.readyState === 4 && this.status === 200) {
                    progress++
                    handle.classList.add('generate')
                    if (progressBar) {
                        let allItems = element.querySelectorAll('.handle-item-prototype')
                        let executedItems = element.querySelectorAll('.handle-item-prototype.generate')
                        let percent = executedItems.length > 0 ? Math.ceil(((executedItems.length - allItems.length) / allItems.length) * 100) + 100 : 100
                        progressBar.style.width = percent + '%'
                        progressBar.setAttribute('aria-valuenow', percent + '%')
                        progressBar.innerText = percent + '%'
                    }
                    if (progress === elements.length) {
                        window.location.replace(window.location.href)
                    } else {
                        setPosition();
                    }
                }
            }
        }
        setPosition()
    }

    let sortables = document.querySelectorAll('.prototype-sortable');

    sortables.forEach(function (el) {

        let elId = el.getAttribute('id');
        let asDeletable = el.querySelectorAll('.swal-delete-link');
        let loader = el.querySelector('.prototype-preloader');
        let itemsClass = el.querySelectorAll('.prototype-block-group').length > 0 ? '.prototype-block-group' : '.prototype-block';

        if (asDeletable.length > 0) {
            el.querySelectorAll('.prototype-block').forEach(item => item.classList.add('as-deletable'));
        }

        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.sortable !== 'undefined') {
            let sortable = jQuery(el).sortable({
                placeholder: "ui-state-highlight",
                items: itemsClass,
                handle: ".handle-item-prototype",
                start: function (e, ui) {
                    ui.placeholder.width(ui.item.width());
                    ui.placeholder.height(ui.item.height());
                },
                update: function (event, ui) {

                    if (loader) loader.classList.remove('d-none');
                    let items = el.querySelectorAll('.handle-item-prototype');

                    if (typeof bootstrap !== 'undefined') {
                        let tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                        tooltips.forEach(t => {
                            let instance = bootstrap.Tooltip.getInstance(t);
                            if (instance) instance.hide();
                        });
                    }

                    items.forEach(function (itemEl, i) {
                        itemEl.setAttribute('data-position', (i + 1).toString());
                        itemEl.classList.add('in-progress');
                    });

                    let firstItem = items[0];
                    if (firstItem) {
                        let form = firstItem.closest('form');
                        if (form) {
                            let xHttp = new XMLHttpRequest()
                            xHttp.open("POST", form.getAttribute('action') + '?ajax=true', true)
                            xHttp.send(new FormData(form))
                            xHttp.onload = function (e) {
                                if (this.readyState === 4 && this.status === 200) {
                                    setPositions(elId);
                                }
                            }
                        }
                    } else {
                        setPositions(elId);
                    }
                }
            });
        }
    });
}