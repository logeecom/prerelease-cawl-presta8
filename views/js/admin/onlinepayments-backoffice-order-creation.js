function adjustBackOfficeOrderCreation(module) {

    let payByLinkTitle = $(`input[name="${module}-pay-by-link-title"]`).val();

    $(`#cart_summary_payment_module option[value="${module}"]`).text(payByLinkTitle);
    let paymentSelectInput = $('#cart_summary_payment_module');
    let expiresAt = $(`#${module}-expires-at`);
    let paymentSelectDiv = paymentSelectInput.parent().parent();
    let createOrderButton = $('#create-order-button');

    expiresAt.insertAfter(paymentSelectDiv);

    if(paymentSelectInput.val() === module) {
        expiresAt.show();
    }

    /**
     * Disable create order button if date is in invalid form.
     */
    expiresAt.on('click change' , () => {

        let selectedDate = $(`#${module}-expires-at-date`).val();
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
     * Display plugin form if selected.
     */
    paymentSelectInput.on('change', () => {
        let pluginSelected = paymentSelectInput.val() === module;
        if (pluginSelected) {
            expiresAt.show();

            return;
        }

        expiresAt.hide();
    });
}