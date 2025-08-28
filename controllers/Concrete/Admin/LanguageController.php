<?php

namespace OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;

/**
 * Class LanguageController
 *
 * @package OnlinePayments\Controllers\Concrete\Admin
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
