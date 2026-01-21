import '../../../scss/admin/pages/seo.scss';
import '../../../scss/vendor/components/_prism.scss';

import '../../vendor/plugins/prism';
import preview from './seo/preview';
import search from './seo/search';

$(function () {

    search();

    $(document).keyup(function () {
        preview();
    });

    $('#v-pills-tab-tree .entities-list').find('.link-item.active').parents('.nested').addClass('active');

    $('body').on('change', '.is-index', function () {
        let el = $(this);
        let prism = $('#highlight-preview');
        let value = el.is(':checked') ? 'index' : 'noindex';
        prism.find('.highlight-index').html('&lt;meta name="robots" content="' + value + '" />');
        Prism.highlightElement($('.highlight-index')[0]);
    });
});