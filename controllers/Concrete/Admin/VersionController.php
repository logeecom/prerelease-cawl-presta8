<?php

namespace CAWL\OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use CAWL\OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
/**
 * Class VersionController
 *
 * @package OnlinePayments\Controllers\Concrete\Admin
 * @internal
 */
class VersionController extends ModuleAdminController
{
    /**
     * @return void
     */
    public function displayAjaxGetVersion() : void
    {
        $result = AdminAPI::get()->version()->getVersionInfo();
        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
}
