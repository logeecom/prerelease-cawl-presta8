<?php

namespace CAWL\OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use CAWL\OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
class OrderStatusesController extends ModuleAdminController
{
    public function displayAjaxGetOrderStatuses()
    {
        $storeId = \Tools::getValue('storeId');
        $result = AdminAPI::get()->store($storeId)->getStoreOrderStatuses();
        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
}
