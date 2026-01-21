import scrollToEl from "../../../vendor/components/scroll-to";

document.addEventListener("DOMContentLoaded", () => {

    let screenWidth = window.screen.width;

    let teaser = function () {
        document.querySelectorAll('.newscast-teaser-vertical').forEach((teaser) => {
            let container = teaser.closest('.container-fluid-right');
            if (container) {
                container.classList.add('as-newscast-teaser');
            }
            if (screenWidth > 767) {
                let catElWidth = 0;
                let contentWidth = 0;
                teaser.querySelectorAll('.nav-link').forEach((el) => {
                    const catEl = el.querySelector('.category-wrap');
                    let catWidth = catEl ? catEl.offsetWidth : 0;
                    let width = el.offsetWidth;
                    if (catWidth > catElWidth) {
                        catElWidth = catWidth;
                        contentWidth = width - catElWidth;
                    }
                });
                teaser.querySelectorAll('.nav-link').forEach((el) => {
                    const catEl = el.querySelector('.category-wrap');
                    const contentEl = el.querySelector('.content');
                    if (catEl) {
                        catEl.style.width = catElWidth + 'px';
                    }
                    if (contentEl) {
                        contentEl.style.width = contentWidth + 'px';
                    }
                });
            }

            if (container && screenWidth <= 991) {
                teaser.querySelectorAll('.nav-link').forEach((link) => {
                    link.onclick = function () {
                        const target = document.querySelector(link.dataset.bsTarget);
                        console.log(link.dataset.bsTarget);
                        console.log(link);
                        console.log(target);
                        if (target) {
                            scrollToEl(target);
                        }
                    }
                });
                container.classList.remove('container-fluid-right');
                let row = container.querySelector('.row-container');
                if (container) {
                    row.classList.remove('row-container');
                }
            }
        });
    }

    teaser();
    window.addEventListener('resize', function () {
        teaser();
    });
});