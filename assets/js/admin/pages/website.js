import '../../../scss/admin/pages/website.scss';

document.body.addEventListener('change', function (e) {
    const inputTheme = e.target.closest('.input-theme');
    if (!inputTheme) return;

    const group = inputTheme.closest('.themes-group');
    if (!group) return;

    const inputs = group.querySelectorAll('.input-theme');
    inputs.forEach(function (input) {
        let card = input.closest('.card');
        if (input.checked && !input.classList.contains('active')) {
            input.classList.add('active');
            if (card) card.classList.add('active');
        } else if (!input.checked) {
            input.classList.remove('active');
            if (card) card.classList.remove('active');
        }
    });
});