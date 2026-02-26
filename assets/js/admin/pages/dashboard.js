import '../../../scss/admin/pages/dashboard.scss';

/** 4 - Remove saying href attribute */
const saying = document.getElementById('saying');
if (saying) {
    saying.querySelectorAll('a').forEach(link => {
        link.removeAttribute('href');
        link.classList.add('text-info');
    });
}