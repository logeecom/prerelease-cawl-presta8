var Worldlineop = Worldlineop || {};

$(document).ready(function () {
    $(document).on('click', '#worldlineop-copy-payment-link', function () {
        navigator.clipboard.writeText($("#payByLinkGeneratedUrl").val());
    });
});