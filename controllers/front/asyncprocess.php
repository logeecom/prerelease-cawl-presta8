<?php

namespace {
    if (!\defined('_PS_VERSION_')) {
        exit;
    }
    use CAWL\OnlinePayments\Controllers\Concrete\Front\AsyncProcessModuleFrontController;
    /** @internal */
    class CawlopAsyncProcessModuleFrontController extends AsyncProcessModuleFrontController
    {
    }
    /** @internal */
    \class_alias('CAWL\\CawlopAsyncProcessModuleFrontController', 'CawlopAsyncProcessModuleFrontController', \false);
}
