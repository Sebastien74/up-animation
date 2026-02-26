document.addEventListener('click', function (e) {
    const caret = e.target.closest('.tree-list .caret');
    if (caret) {
        const item = caret.closest('li.item');
        if (item) {
            const child = item.querySelector('.nested');
            if (child) {
                child.classList.toggle('active');
            }
        }
    }
});