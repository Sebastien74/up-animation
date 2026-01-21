export default function(body, count) {

    let countInt = parseInt(count);
    let counter = body.find('#result-counter .text');

    if(countInt === 0) {
        counter.text(counter.data('none'));
    }
    else if(countInt === 1) {
        counter.text(count + ' ' + counter.data('one'));
    }
    else {
        counter.text(count + ' ' + counter.data('multi'));
    }
}