import '../bootstrap/dist/modal';
import '../bootstrap/dist/alert';

/**
 * To display Errors messages
 */
export default function (error = null, element = null) {

    if (!error) {
        return false;
    }

    let isDebug = $('html').data('debug');

    if (error.status !== 200 && isDebug) {

        let body = $('body');

        body.find('.alert').remove();
        $(".main-preloader").fadeOut();

        let trans = $('#data-translation');
        let text = error;
        let status = 500;
        let statusText = trans.data('internal-error');

        if (typeof error != 'string') {
            text = error.responseText;
            status = error.status;
            statusText = error.statusText;
        }

        let adminBody = $('#admin-body');
        let blockToDisplay = element === null ? adminBody : (element.length > 0 ? element : adminBody);

        if (body.hasClass('internal') && typeof text != 'undefined') {
            let message = '<div class="internal-error-alert alert alert-danger position-relative d-flex p-0 mt-3">';
            message += '<div class="icon d-flex align-items-center justify-content-center position-relative">';
            message += '<svg xmlns="http://www.w3.org/2000/svg" height="22" viewBox="0 0 576 512"><path d="M248.747 204.705l6.588 112c.373 6.343 5.626 11.295 11.979 11.295h41.37a12 12 0 0 0 11.979-11.295l6.588-112c.405-6.893-5.075-12.705-11.979-12.705h-54.547c-6.903 0-12.383 5.812-11.978 12.705zM330 384c0 23.196-18.804 42-42 42s-42-18.804-42-42 18.804-42 42-42 42 18.804 42 42zm-.423-360.015c-18.433-31.951-64.687-32.009-83.154 0L6.477 440.013C-11.945 471.946 11.118 512 48.054 512H527.94c36.865 0 60.035-39.993 41.577-71.987L329.577 23.985zM53.191 455.002L282.803 57.008c2.309-4.002 8.085-4.002 10.394 0l229.612 397.993c2.308 4-.579 8.998-5.197 8.998H58.388c-4.617.001-7.504-4.997-5.197-8.997z"/></svg>';
            message += '</div>';
            message += '<div class="message px-4 py-3 w-100">';
            message += text;
            message += '</div>';
            message += '</div>';
            blockToDisplay.prepend(message);
        }

        if (status !== 0 && statusText !== "error") {
            let message = '<div class="internal-error-alert alert alert-danger position-relative d-flex p-0 mt-3">';
            message += '<div class="icon d-flex align-items-center justify-content-center position-relative">';
            message += '<svg xmlns="http://www.w3.org/2000/svg" height="22" viewBox="0 0 576 512"><path d="M248.747 204.705l6.588 112c.373 6.343 5.626 11.295 11.979 11.295h41.37a12 12 0 0 0 11.979-11.295l6.588-112c.405-6.893-5.075-12.705-11.979-12.705h-54.547c-6.903 0-12.383 5.812-11.978 12.705zM330 384c0 23.196-18.804 42-42 42s-42-18.804-42-42 18.804-42 42-42 42 18.804 42 42zm-.423-360.015c-18.433-31.951-64.687-32.009-83.154 0L6.477 440.013C-11.945 471.946 11.118 512 48.054 512H527.94c36.865 0 60.035-39.993 41.577-71.987L329.577 23.985zM53.191 455.002L282.803 57.008c2.309-4.002 8.085-4.002 10.394 0l229.612 397.993c2.308 4-.579 8.998-5.197 8.998H58.388c-4.617.001-7.504-4.997-5.197-8.997z"/></svg>';
            message += '</div>';
            message += '<div class="message px-4 py-3 w-100">';
            message += '<strong class="me-2">' + trans.data('error') + ' ' + status + '</strong>' + statusText;
            message += '</div>';
            message += '</div>';
            blockToDisplay.prepend(message);
        }

        $('.stripe-preloader').addClass('d-none');
        $('.modal').modal('hide');
        $('.alert').alert();

        let errorEl = body.find('.page-wrapper > .container-fluid .internal-error-alert').first();

        if (errorEl) {
            let offsetData = errorEl.offset();
            if (offsetData) {
                let elOffset = offsetData.top;
                let elHeight = errorEl.height();
                let windowHeight = $(window).height();
                let offset;
                if (elHeight < windowHeight) {
                    offset = elOffset - ((windowHeight / 2) - (elHeight / 2));
                } else {
                    offset = elOffset;
                }
                $('html, body').animate({scrollTop: offset}, 700);
            }
        }
    }
}