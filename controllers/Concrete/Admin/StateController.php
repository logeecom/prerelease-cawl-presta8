<?php

namespace CAWL\OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use CAWL\OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
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
    public function displayAjaxIndex() : void
    {
        $storeId = Tools::getValue('storeId');
        $result = AdminAPI::get()->integration($storeId)->getState();
        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
}
