$(document).ready(function() {
    /**
     * System Indicator Extension
     */
    let $el = $('#system-indicator-extension');
    let url = $el.data('url');
    $.get(url, function (data, status) {
        $el.html(data);

        if ($el.find('.color-error').length !== 0) {
            $('#system-indicator-extension--icon').addClass('icon-hint');
        }

        $el.click(function(e) {
            e.stopPropagation();
        });

        $('#system-indicator-extension--clear-cache').off('click').click(function( event ) {
            event.preventDefault();
            let ccurl = $(this).attr('href');
            let $spinner = $('#system-indicator-extension--clear-cache--spinner');
            $spinner.css('display', 'inline');
            $.get(ccurl, function (data, status) {
                console.log('[SystemInformationBundle] Clear cache command result: ' + data);
            })
            .always(function() {
                $spinner.hide();
            });
        });
    });
});