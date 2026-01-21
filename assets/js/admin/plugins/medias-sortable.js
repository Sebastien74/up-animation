import route from "../../vendor/components/routing";

/**
 * Medias sortable
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let loader = document.getElementById("medias-sortable-preloader");
    let progressBarCard = loader.querySelector(".progress-card");

    if (progressBarCard) {

        let progressBarCardContainer = progressBarCard.closest(".progress-card-container");
        let progressBar = loader.querySelector(".position-progress-bar");
        let elementToScroll = progressBarCardContainer != null && typeof progressBarCardContainer != 'undefined' ? progressBarCardContainer : progressBarCard;

        let sortable = $('#medias-sortable-container').sortable({
            placeholder: "ui-state-highlight",
            items: '.sortable-item',
            handle: ".handle-item",
            start: function (e, ui) {
                ui.placeholder.height(ui.item.height());
            },
            update: function (event, ui) {

                loader.classList.remove('d-none')
                progressBarCard.classList.remove('d-none')

                let body = $('body');
                let items = body.find('.sortable-item');
                let website = body.data('id');

                $('[data-bs-toggle="tooltip"]').tooltip('hide');

                items.each(function (i, el) {
                    let elementId = $(el).attr('id');
                    $('#' + elementId).attr('data-position', (i + 1));
                });

                setPosition(website);
            }
        });

        // sortable.disableSelection();

        function setPosition(website) {

            let items = document.getElementById('medias-sortable-container').querySelectorAll(".sortable-item:not(.executed)");

            if (items.length > 0) {

                let item = items[0];
                let elsDataLocale = item.getElementsByClassName('media-locale-data');
                let count = elsDataLocale.length;

                for (let i = 0; i < elsDataLocale.length; i++) {

                    let elData = elsDataLocale[i];
                    let url = route('admin_mediarelation_position', {
                        website: website,
                        mediaRelation: elData.dataset.id,
                        position: item.dataset.position,
                        entityId: item.dataset.entityId,
                        clearCache: item.dataset.clearCache,
                        entityNamespace: item.dataset.classname,
                    });

                    let xHttp = new XMLHttpRequest();
                    xHttp.open("GET", url, true);
                    xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
                    xHttp.send();
                    xHttp.onload = function () {
                        if (this.readyState === 4 && this.status === 200) {
                            let currentCount = i + 1;
                            if (currentCount === count) {
                                window.scrollTo({
                                    top: (elementToScroll.getBoundingClientRect().top + window.scrollY) - 20,
                                    behavior: 'smooth'
                                });
                                item.classList.add('executed');
                                let container = document.getElementById('medias-sortable-container');
                                let allItems = container.querySelectorAll(".sortable-item");
                                let executedItems = container.querySelectorAll(".sortable-item.executed");
                                let progress = executedItems.length > 0 ? Math.ceil(((executedItems.length - allItems.length) / allItems.length) * 100) + 100 : 100;
                                progressBar.style.width = progress + '%';
                                progressBar.setAttribute('aria-valuenow', progress + '%');
                                progressBar.innerText = progress + '%';
                                setPosition(website);
                            }
                        }
                    }
                }
            } else {
                progressBarCard.classList.add('d-none');
                progressBar.style.width = 0;
                progressBar.innerText = '';
                progressBar.setAttribute('aria-valuenow', '0');
                loader.classList.add('d-none');
                let items = document.getElementById('medias-sortable-container').querySelectorAll(".sortable-item");
                for (let i = 0; i < items.length; i++) {
                    items[i].classList.remove('executed');
                }
            }
        }
    }
}