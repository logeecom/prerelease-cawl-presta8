<?php

namespace OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;

/**
 * Class StoresController.
 *
 * @package OnlinePayments\Controllers\Concrete\Admin
 */
class StoresController extends ModuleAdminController
{
    /**
     * @return void
     */
    public function displayAjaxGetStores(): void
    {
        $result = AdminAPI::get()->store('')->getStores();

        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }

    /**
     * @return void
     */
    public function displayAjaxGetCurrentStore(): void
    {
        $result = AdminAPI::get()->store('')->getCurrentStore();

        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
}