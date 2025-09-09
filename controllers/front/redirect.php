<?php

namespace {
    if (!\defined('_PS_VERSION_')) {
        exit;
    }
    use CAWL\OnlinePayments\Controllers\Concrete\Front\RedirectModuleFrontController;
    /** @internal */
    class CawlopRedirectModuleFrontController extends RedirectModuleFrontController
    {
    }
    /** @internal */
    \class_alias('CAWL\\CawlopRedirectModuleFrontController', 'CawlopRedirectModuleFrontController', \false);
}
