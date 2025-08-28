<?php

namespace OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use Tools;

/**
 * Class StateController
 *
 * @package OnlinePayments\Controllers\Concrete\Admin
 */
class StateController extends ModuleAdminController
{
    /**
     * @return void
     */
    public function displayAjaxIndex(): void
    {
        $storeId = Tools::getValue('storeId');

        $result = AdminAPI::get()->integration($storeId)->getState();

        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
}
