import 'nestable3';
import route from "../../vendor/components/routing";

/**
 * Medias reordering — driven by Nestable3 (the same plugin as the
 * tree-sortable used for pages). Flat list (maxDepth: 1), no nested
 * children. The .dd-item / .dd-handle convention is shared with the
 * tree sortable for visual + structural consistency.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    const loader = document.getElementById("medias-sortable-preloader");
    if (!loader) {
        return;
    }

    import('../../../scss/admin/core/_nestable-medias.scss');

    const container = document.getElementById('medias-sortable-container');
    if (!container || typeof jQuery === 'undefined' || typeof jQuery.fn.nestable === 'undefined') {
        return;
    }

    jQuery(container).nestable({
        maxDepth: 1,
        expandBtnHTML: '',
        collapseBtnHTML: '',
        expandContentBtnHTML: '',
        collapseContentBtnHTML: '',
        callback: function () {
            persistOrder();
        }
    });

    function persistOrder() {

        loader.classList.remove('d-none');

        const body = document.body;
        const items = container.querySelectorAll(':scope > .dd-item');
        const website = body.dataset.id;

        if (typeof bootstrap !== 'undefined') {
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(t => {
                const instance = bootstrap.Tooltip.getInstance(t);
                if (instance) instance.hide();
            });
        }

        items.forEach((el, i) => {
            el.setAttribute('data-position', (i + 1).toString());
        });

        const data = {
            entityNamespace: items.length > 0 ? items[0].dataset.classname : null,
            items: []
        };

        items.forEach((item) => {
            const mediaRelationIds = [];
            const elsDataLocale = item.getElementsByClassName('media-locale-data');
            for (let i = 0; i < elsDataLocale.length; i++) {
                mediaRelationIds.push(elsDataLocale[i].dataset.id);
            }
            data.items.push({
                entityId: item.dataset.entityId,
                position: item.dataset.position,
                mediaRelationIds: mediaRelationIds
            });
        });

        if (data.items.length === 0) {
            loader.classList.add('d-none');
            return;
        }

        const url = route('admin_mediarelation_positions', {website: website});
        const xHttp = new XMLHttpRequest();
        xHttp.open("POST", url, true);
        xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
        xHttp.send(JSON.stringify(data));
        xHttp.onload = function () {
            if (this.readyState === 4 && this.status === 200) {
                setTimeout(function () {
                    loader.classList.add('d-none');
                }, 300);
            }
        };
    }
}
