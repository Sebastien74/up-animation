import scrollToEl from "../../../vendor/components/scroll-to";
import {debounce} from "../functions";

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
                
                const linksData = Array.from(teaser.querySelectorAll('.nav-link')).map((el) => {
                    const catEl = el.querySelector('.category-wrap');
                    return {
                        el,
                        catEl,
                        catWidth: catEl ? catEl.offsetWidth : 0,
                        width: el.offsetWidth,
                        contentEl: el.querySelector('.content')
                    };
                });

                linksData.forEach(data => {
                    if (data.catWidth > catElWidth) {
                        catElWidth = data.catWidth;
                        contentWidth = data.width - catElWidth;
                    }
                });

                requestAnimationFrame(() => {
                    linksData.forEach((data) => {
                        if (data.catEl) {
                            data.catEl.style.width = catElWidth + 'px';
                        }
                        if (data.contentEl) {
                            data.contentEl.style.width = contentWidth + 'px';
                        }
                    });
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
    window.addEventListener('resize', debounce(function () {
        teaser();
    }, 250));
});