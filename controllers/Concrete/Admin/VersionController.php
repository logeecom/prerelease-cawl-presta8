<?php

namespace OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;

/**
 * Class VersionController
 *
 * @package OnlinePayments\Controllers\Concrete\Admin
 */
class VersionController extends ModuleAdminController
{
    /**
     * @return void
     */
    public function displayAjaxGetVersion(): void
    {
        $result = AdminAPI::get()->version()->getVersionInfo();

        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
}
