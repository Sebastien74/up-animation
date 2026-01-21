/**
 * Counter
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (onlyUpdate = false) {

    const counterFormGroups = document.querySelectorAll('.counter-form-group');
    counterFormGroups.forEach(counterFormGroup => {
        let input = counterFormGroup.querySelector('input, textarea')
        if (input) {
            count(counterFormGroup, input)
            if (!onlyUpdate) {
                input.addEventListener('input', ev => {
                    count(counterFormGroup, input)
                })
            }
        }
    })

    function count(counterFormGroup, input) {
        let count = input.value.length;
        let counter = counterFormGroup.querySelector('.char-counter');
        let limit = counter.getAttribute('data-limit');
        counter.querySelector('.count').textContent = count;
        if (count > limit) {
            counter.classList.remove('bg-info', 'bg-success');
            counter.classList.add('bg-danger');
        } else if (count === 0) {
            counter.classList.remove('bg-danger', 'bg-success');
            counter.classList.add('bg-info');
        } else {
            counter.classList.remove('bg-danger', 'bg-info');
            counter.classList.add('bg-success');
        }
    }
}