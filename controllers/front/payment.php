<?php

namespace {
    if (!\defined('_PS_VERSION_')) {
        exit;
    }
    use CAWL\OnlinePayments\Controllers\Concrete\Front\PaymentModuleFrontController;
    /** @internal */
    class CawlopPaymentModuleFrontController extends PaymentModuleFrontController
    {
    }
    /** @internal */
    \class_alias('CAWL\\CawlopPaymentModuleFrontController', 'CawlopPaymentModuleFrontController', \false);
}
