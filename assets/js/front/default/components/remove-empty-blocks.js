/** To remove empty blocks */
const inRemoveView = document.querySelector(".remove-unused-block");
if (inRemoveView) {
    inRemoveView.querySelectorAll('.layout-block').forEach(function (block) {
        let contentEl = block.querySelector('.layout-block-content');
        if (contentEl) {
            let children = [...contentEl.children];
            if (children.length === 0 || (children.length === 1 && children[0].classList.contains('webmaster-link-edit'))) {
                let col = block.closest('.layout-col');
                let zone = col.closest('.layout-zone');
                block.remove();
                if (col.querySelectorAll('.layout-block').length === 0) {
                    col.remove();
                }
                if (zone.querySelectorAll('.layout-col').length === 0) {
                    zone.remove();
                }
                console.log('Block removed');
            }
        }
    });
}