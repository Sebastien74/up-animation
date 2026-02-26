document.addEventListener('DOMContentLoaded', function () {
    "use strict";

    const preloader = document.querySelector(".preloader");
    if (preloader) {
        preloader.style.display = 'none';
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('.mega-dropdown')) {
            e.stopPropagation();
        }
    });

    // ==============================================================
    // This is for the top header part and sidebar part
    // ==============================================================
    const set = function () {
        const width = (window.innerWidth > 0) ? window.innerWidth : screen.width;
        const body = document.body;
        const brandSpans = document.querySelectorAll('.navbar-brand span');
        const sidebarIcons = document.querySelectorAll(".sidebartoggler i");

        if (width < 1170) {
            body.classList.add("mini-sidebar");
            brandSpans.forEach(span => span.style.display = 'none');
            sidebarIcons.forEach(i => i.classList.add("ti-menu"));
        } else {
            body.classList.remove("mini-sidebar");
            brandSpans.forEach(span => span.style.display = 'inline');
        }
    };

    window.addEventListener('load', set);
    window.addEventListener("resize", set);

    // ==============================================================
    // Theme options
    // ==============================================================
    document.querySelectorAll(".sidebartoggler").forEach(el => {
        el.addEventListener('click', function () {
            const body = document.body;
            const brandSpans = document.querySelectorAll('.navbar-brand span');

            if (body.classList.contains("mini-sidebar")) {
                window.dispatchEvent(new Event('resize'));
                body.classList.remove("mini-sidebar");
                brandSpans.forEach(span => span.style.display = 'inline');
            } else {
                window.dispatchEvent(new Event('resize'));
                body.classList.add("mini-sidebar");
                brandSpans.forEach(span => span.style.display = 'none');
            }
        });
    });

    // this is for close icon when navigation open in mobile view
    document.querySelectorAll(".nav-toggler").forEach(el => {
        el.addEventListener('click', function () {
            document.body.classList.toggle("show-sidebar");
            const icons = this.querySelectorAll("i");
            icons.forEach(i => {
                i.classList.toggle("ti-menu");
                i.classList.add("ti-close");
            });
        });
    });

    document.querySelectorAll(".search-box a, .search-box .app-search .srh-btn").forEach(el => {
        el.addEventListener('click', function () {
            const appSearch = document.querySelector(".app-search");
            if (appSearch) {
                if (window.getComputedStyle(appSearch).display === 'none') {
                    appSearch.style.display = 'block';
                } else {
                    appSearch.style.display = 'none';
                }
            }
        });
    });

    // ==============================================================
    // Right sidebar options
    // ==============================================================
    document.querySelectorAll(".right-side-toggle").forEach(el => {
        el.addEventListener('click', function () {
            const rightSidebar = document.querySelector(".right-sidebar");
            if (rightSidebar) {
                rightSidebar.style.display = 'block';
                rightSidebar.classList.toggle("shw-rside");
            }
        });
    });

    // ==============================================================
    // This is for the floating labels
    // ==============================================================
    document.querySelectorAll('.floating-labels .form-control').forEach(el => {
        const toggleFocused = (e) => {
            const group = el.closest('.form-group');
            if (group) {
                group.classList.toggle('focused', (e.type === 'focus' || el.value.length > 0));
            }
        };
        el.addEventListener('focus', toggleFocused);
        el.addEventListener('blur', toggleFocused);
        // Trigger blur initially
        toggleFocused({type: 'blur'});
    });

    // ==============================================================
    //tooltip
    // ==============================================================
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // ==============================================================
    //Popover
    // ==============================================================
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });

    // ==============================================================
    // Perfact scrollbar
    // ==============================================================
    if (typeof PerfectScrollbar !== 'undefined') {
        document.querySelectorAll('.scroll-sidebar, .right-side-panel, .message-center, .right-sidebar, #chat, #msg, #comment, #todo').forEach(el => {
            new PerfectScrollbar(el);
        });
    }

    // ==============================================================
    // Resize all elements
    // ==============================================================
    window.dispatchEvent(new Event('resize'));

    // ==============================================================
    // To do list
    // ==============================================================
    document.querySelectorAll(".list-task li label").forEach(el => {
        el.addEventListener('click', function () {
            this.classList.toggle("task-done");
        });
    });

    // ==============================================================
    // Collapsable cards
    // ==============================================================
    document.querySelectorAll('a[data-action="collapse"]').forEach(el => {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            const card = this.closest('.card');
            if (card) {
                const icon = card.querySelector('[data-action="collapse"] i');
                if (icon) {
                    icon.classList.toggle('ti-minus');
                    icon.classList.toggle('ti-plus');
                }
                const cardBody = card.querySelector('.card-body');
                if (cardBody && typeof bootstrap !== 'undefined') {
                    const bsCollapse = bootstrap.Collapse.getOrCreateInstance(cardBody);
                    bsCollapse.toggle();
                }
            }
        });
    });

    // Toggle fullscreen
    document.querySelectorAll('a[data-action="expand"]').forEach(el => {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            const card = this.closest('.card');
            if (card) {
                const icon = card.querySelector('[data-action="expand"] i');
                if (icon) {
                    icon.classList.toggle('mdi-arrow-expand');
                    icon.classList.toggle('mdi-arrow-compress');
                }
                card.classList.toggle('card-fullscreen');
            }
        });
    });

    // Close Card
    document.querySelectorAll('a[data-action="close"]').forEach(el => {
        el.addEventListener('click', function () {
            const card = this.closest('.card');
            if (card) {
                card.style.transition = 'opacity 0.5s ease-out';
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 500);
            }
        });
    });

    // ==============================================================
    // Color variation
    // ==============================================================

    const mySkins = [
        "skin-default",
        "skin-green",
        "skin-red",
        "skin-blue",
        "skin-purple",
        "skin-megna",
        "skin-default-dark",
        "skin-green-dark",
        "skin-red-dark",
        "skin-blue-dark",
        "skin-purple-dark",
        "skin-megna-dark"
    ]

    function get(name) {
        if (typeof (Storage) !== 'undefined') {
            return localStorage.getItem(name)
        } else {
            window.alert('Please use a modern browser to properly view this template!')
        }
    }

    function store(name, val) {
        if (typeof (Storage) !== 'undefined') {
            localStorage.setItem(name, val)
        } else {
            window.alert('Please use a modern browser to properly view this template!')
        }
    }

    function changeSkin(cls) {
        mySkins.forEach(skin => {
            document.body.classList.remove(skin);
        });
        document.body.classList.add(cls);
        store('skin', cls);
        return false;
    }

    function setup() {
        const tmp = get('skin');
        if (tmp && mySkins.includes(tmp)) {
            changeSkin(tmp);
        }
        document.querySelectorAll('[data-skin]').forEach(el => {
            el.addEventListener('click', function (e) {
                if (this.classList.contains('knob')) return;
                e.preventDefault();
                changeSkin(this.dataset.skin);
            });
        });
    }

    setup();

    const themeColors = document.querySelector("#themecolors");
    if (themeColors) {
        themeColors.addEventListener("click", function (e) {
            const target = e.target.closest('a');
            if (target) {
                themeColors.querySelectorAll("li a").forEach(a => a.classList.remove("working"));
                target.classList.add("working");
            }
        });
    }

    // For Custom File Input
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('custom-file-input')) {
            const fileName = e.target.value;
            const nextLabel = e.target.nextElementSibling;
            if (nextLabel && nextLabel.classList.contains('custom-file-label')) {
                nextLabel.innerHTML = fileName;
            }
        }
    });
});
