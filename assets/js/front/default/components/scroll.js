/**
 * Scroll event.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let btn = document.getElementById('scroll-top-btn');
    let screenWidth = window.screen.width;
    let breakpoints = {
        sm: 576,
        md: 768,
        lg: 992,
        xl: 1200,
        xxl: 1600
    }
    let breakpoint = breakpoints['lg'];

    /** To scroll to top of the page */
    let showBtn = function (btn) {
        if (window.scrollY > 300) {
            if (!btn.classList.contains('show')) {
                btn.classList.remove('d-none');
                btn.classList.add('show');
            }
        } else if (!btn.classList.contains('active')) {
            btn.classList.add('d-none');
            btn.classList.remove('show');
        }
    }

    if (document.body.contains(btn) && screenWidth >= breakpoint) {
        showBtn(btn);
        window.onscroll = function () {
            showBtn(btn);
        }
        btn.onclick = function (event) {
            btn.classList.add('active');
            event.preventDefault();
            window.scrollTo({top: 0, behavior: 'smooth'});
            document.activeElement.blur();
            setTimeout(function () {
                btn.classList.remove('active');
            }, 1500);
            setTimeout(function () {
                btn.classList.remove('show');
            }, 1000);
        }
    }

    /** To scroll to element */
    let links = document.querySelectorAll('.scroll-link');
    links.forEach(link => {
        link.onclick = function (event) {
            let target = document.querySelectorAll(link.dataset.target);
            if (typeof target != 'undefined') {
                event.preventDefault();
                window.scrollTo({top: target[0].offsetTop, behavior: 'smooth'});
            }
        }
    });
}