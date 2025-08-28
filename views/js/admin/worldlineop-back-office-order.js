$(document).ready(function () {
    let payByLinkTitle = $('input[name="worldline-pay-by-link-title"]').val();
    $("#cart_summary_payment_module option[value='worldlineop']").text(payByLinkTitle);
    let paymentSelectInput = $('#cart_summary_payment_module');
    let expiresAt = $("#worldline-expires-at");
    let paymentSelectDiv = paymentSelectInput.parent().parent();
    let createOrderButton = $('#create-order-button');

    expiresAt.insertAfter(paymentSelectDiv);

    if(paymentSelectInput.val() === 'worldlineop') {
        expiresAt.show();
    }

    /**
     * Disable create order button if date is in invalid form.
     */
    expiresAt.on('click change' , () => {

        let selectedDate = $("#worldline-expires-at-date").val();
        if(!selectedDate){
            createOrderButton.prop('disabled', true);

            return;
        }

        let selected = new Date(selectedDate);
        let today = new Date();

        if(selected <= today) {
            createOrderButton.prop('disabled', true);

            return;
        }

        createOrderButton.prop('disabled', false);
    });

    /**
     * Display Worldline form if selected.
     */
    paymentSelectInput.on('change', () => {
        let worldlineSelected = paymentSelectInput.val() === 'worldlineop';
        if (worldlineSelected) {
            expiresAt.show();

            return;
        }

        expiresAt.hide();
    });
});
