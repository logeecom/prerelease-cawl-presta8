<?php

namespace CAWL\OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use CAWL\OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
/**
 * Class LanguageController
 *
 * @package OnlinePayments\Controllers\Concrete\Admin
 * @internal
 */
class LanguageController extends ModuleAdminController
{
    public function displayAjaxGetLanguages()
    {
        $storeId = \Tools::getValue('storeId');
        $result = AdminAPI::get()->language($storeId)->getLanguages();
        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
}
