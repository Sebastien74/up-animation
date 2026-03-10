import route from "../../vendor/components/routing";

export default function () {

    let loader = document.getElementById("medias-sortable-preloader");
    if (loader) {
        import('../../../scss/admin/core/_nestable-medias.scss');
    }

    let progressBarCard = loader ? loader.querySelector(".progress-card") : null;

    if (progressBarCard) {

        let progressBarCardContainer = progressBarCard.closest(".progress-card-container");
        let progressBar = loader.querySelector(".position-progress-bar");
        let elementToScroll = progressBarCardContainer != null && typeof progressBarCardContainer != 'undefined' ? progressBarCardContainer : progressBarCard;

        let sortableEl = document.getElementById('medias-sortable-container');
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.sortable !== 'undefined' && sortableEl) {
            jQuery(sortableEl).sortable({
                placeholder: "ui-state-highlight",
                items: '.sortable-item',
                handle: ".handle-item",
                start: function (e, ui) {
                    ui.placeholder.height(ui.item.height());
                },
                update: function (event, ui) {

                    loader.classList.remove('d-none')
                    progressBarCard.classList.remove('d-none')

                    let body = document.body;
                    let items = body.querySelectorAll('.sortable-item');
                    let website = body.dataset.id;

                    if (typeof bootstrap !== 'undefined') {
                        let tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                        tooltips.forEach(t => {
                            let instance = bootstrap.Tooltip.getInstance(t);
                            if (instance) instance.hide();
                        });
                    }

                    items.forEach(function (el, i) {
                        el.setAttribute('data-position', (i + 1).toString());
                    });

                    setPosition(website);
                }
            });
        }

        // sortable.disableSelection();

        function setPosition(website) {

            let container = document.getElementById('medias-sortable-container');
            let items = container.querySelectorAll(".sortable-item");
            let data = {
                entityNamespace: items.length > 0 ? items[0].dataset.classname : null,
                items: []
            };

            items.forEach(function (item) {
                let mediaRelationIds = [];
                let elsDataLocale = item.getElementsByClassName('media-locale-data');
                for (let i = 0; i < elsDataLocale.length; i++) {
                    mediaRelationIds.push(elsDataLocale[i].dataset.id);
                }
                data.items.push({
                    entityId: item.dataset.entityId,
                    position: item.dataset.position,
                    mediaRelationIds: mediaRelationIds
                });
            });

            if (data.items.length > 0) {
                let url = route('admin_mediarelation_positions', {website: website});
                let xHttp = new XMLHttpRequest();
                xHttp.open("POST", url, true);
                xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
                xHttp.send(JSON.stringify(data));
                xHttp.onload = function () {
                    if (this.readyState === 4 && this.status === 200) {
                        progressBar.style.width = '100%';
                        progressBar.setAttribute('aria-valuenow', '100%');
                        progressBar.innerText = '100%';
                        setTimeout(function() {
                            progressBarCard.classList.add('d-none');
                            progressBar.style.width = 0;
                            progressBar.innerText = '';
                            progressBar.setAttribute('aria-valuenow', '0');
                            loader.classList.add('d-none');
                        }, 500);
                    }
                }
            } else {
                progressBarCard.classList.add('d-none');
                progressBar.style.width = 0;
                progressBar.innerText = '';
                progressBar.setAttribute('aria-valuenow', '0');
                loader.classList.add('d-none');
            }
        }
    }
}