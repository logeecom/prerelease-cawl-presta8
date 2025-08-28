<?php

namespace OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;

class OrderStatusesController extends ModuleAdminController
{
    public function displayAjaxGetOrderStatuses()
    {
        $storeId = \Tools::getValue('storeId');

        $result = AdminAPI::get()->store($storeId)->getStoreOrderStatuses();

        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
}
