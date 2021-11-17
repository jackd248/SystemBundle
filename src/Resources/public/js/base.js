$(document).ready(function() {
    /**
     * System Indicator Extension
     */
    let $el = $('#system-indicator-extension');
    let url = $el.data('url');
    $.get(url, function (data, status) {
        $el.html(data);

        if ($el.find('.color-error').length !== 0) {
            $('#system-indicator-extension--icon').addClass('color-error');
        }
    });
});