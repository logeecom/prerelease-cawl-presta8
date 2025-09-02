function paymentLinkCopier(module) {

    $(document).on('click', `#${module}-copy-payment-link`, function () {
        const value = $(`#${module}PayByLinkGeneratedUrl`).val();

        navigator.clipboard.writeText(value);
    });

}