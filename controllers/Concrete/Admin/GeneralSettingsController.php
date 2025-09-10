<?php

namespace CAWL\OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
use CAWL\OnlinePayments\Classes\Services\ImageHandler;
use CAWL\OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use CAWL\OnlinePayments\Classes\Utility\Request;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\CardsSettingsRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\LogSettingsRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\PayByLinkSettingsRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\PaymentSettingsRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use Tools;
/**
 * Class GeneralSettingsController
 *
 * @package OnlinePayments\Controllers\Concrete\Admin
 */
class GeneralSettingsController extends ModuleAdminController
{
    /**
     * @var OnlinePaymentsModule
     */
    public $module;
    public function displayAjaxGetGeneralSettings()
    {
        $storeId = Tools::getValue('storeId');
        $result = AdminAPI::get()->generalSettings($storeId)->getGeneralSettings();
        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
    public function displayAjaxSaveCardsSettings()
    {
        $storeId = Tools::getValue('storeId');
        $requestData = Request::getPostData();
        $result = AdminAPI::get()->generalSettings($storeId)->saveCardsSettings(new CardsSettingsRequest($requestData['enable3ds'] ?? null, $requestData['enforceStrongAuthentication'] ?? null, $requestData['enable3dsExemption'] ?? null, $requestData['exemptionType'] ?? null, (float) $requestData['exemptionLimit'] ?? null));
        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
    public function displayAjaxSavePaymentSettings()
    {
        $storeId = Tools::getValue('storeId');
        $requestData = Request::getPostData();
        $result = AdminAPI::get()->generalSettings($storeId)->savePaymentSettings(new PaymentSettingsRequest($requestData['paymentAction'] ?? null, $requestData['automaticCapture'] ?? null, $requestData['numberOfPaymentAttempts'] ?? null, $requestData['applySurcharge'] ?? null, $requestData['paymentCapturedStatus'] ?? (string) \Configuration::get('PS_OS_PAYMENT'), $requestData['paymentErrorStatus'] ?? (string) \Configuration::get('PS_OS_ERROR'), $requestData['paymentPendingStatus'] ?? (string) \Configuration::getGlobalValue($this->module->getBrand()->getCode() . '_PENDING_ORDER_STATUS_ID'), (string) \Configuration::getGlobalValue($this->module->getBrand()->getCode() . '_AWAITING_CAPTURE_STATUS_ID'), (string) \Configuration::getGlobalValue('PS_OS_CANCELED'), (string) \Configuration::get('PS_OS_REFUND')));
        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
    public function displayAjaxSaveLogSettings()
    {
        $storeId = Tools::getValue('storeId');
        $requestData = Request::getPostData();
        $result = AdminAPI::get()->generalSettings($storeId)->saveLogSettings(new LogSettingsRequest($requestData['debugMode'] ?? null, $requestData['logDays'] ?? null));
        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
    public function displayAjaxSavePayByLinkSettings()
    {
        $storeId = Tools::getValue('storeId');
        $requestData = Request::getPostData();
        $result = AdminAPI::get()->generalSettings($storeId)->savePayByLinkSettings(new PayByLinkSettingsRequest($requestData['enabled'] ?? null, $requestData['title'] ?? '', $requestData['expirationTime'] ?? 7));
        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
    public function displayAjaxDisconnect()
    {
        $storeId = Tools::getValue('storeId');
        /** @var ActiveConnectionProvider $activeConnectionProvider */
        $activeConnectionProvider = ServiceRegister::getService(ActiveConnectionProvider::class);
        $mode = StoreContext::doWithStore($storeId, function () use($activeConnectionProvider) {
            return (string) $activeConnectionProvider->get()->getMode();
        });
        ImageHandler::removeDirectoryForStore($storeId, $mode);
        $response = AdminAPI::get()->generalSettings($storeId)->disconnect();
        OnlinePaymentsPrestaShopUtility::dieJson($response);
    }
}
