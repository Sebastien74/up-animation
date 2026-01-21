import zxcvbn from 'zxcvbn';

export default function (input) {

    const wrap = document.getElementById('strength-wrap');
    const bar = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');

    if (!input || !wrap || !bar || !text) {
        return;
    }

    input.addEventListener('input', function (e) {

        const value = e.target.value;
        const isNull = !value;
        const result = zxcvbn(value);
        const score = result.score;

        const strengthLevels = [
            { width: '20%', color: '#ff4d4f', text: text.dataset.veryLow },
            { width: '40%', color: '#ff7a45', text: text.dataset.low },
            { width: '60%', color: '#fadb14', text: text.dataset.medium },
            { width: '80%', color: '#13c2c2', text: text.dataset.strong },
            { width: '100%', color: '#006d75', text: text.dataset.veryStrong }
        ];

        const level = !isNull ? strengthLevels[score] : { width: '0%', color: '#ff4d4f', text: '' };

        if (isNull) {
            wrap.classList.add('d-none');
            wrap.classList.remove('d-flex');
        } else {
            wrap.classList.remove('d-none');
            wrap.classList.add('d-flex');
        }

        bar.style.width = level.width;
        bar.style.backgroundColor = level.color;
        text.innerText = level.text;
    });
}