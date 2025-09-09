<?php

namespace CAWL\OnlinePayments\Controllers\Concrete\Admin;

use ModuleAdminController;
use CAWL\OnlinePayments\Classes\Services\ImageHandler;
use CAWL\OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use CAWL\OnlinePayments\Classes\Utility\Request;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Request\PaymentMethodRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use Tools;
/**
 * Class PaymentsController
 *
 * @package OnlinePayments\Controllers\Concrete\Admin
 * @internal
 */
class PaymentsController extends ModuleAdminController
{
    public function displayAjaxList()
    {
        $storeId = Tools::getValue('storeId');
        $result = AdminAPI::get()->payment($storeId)->list();
        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
    public function displayAjaxEnable()
    {
        $storeId = Tools::getValue('storeId');
        $requestData = Request::getPostData();
        $result = AdminAPI::get()->payment($storeId)->enable($requestData['paymentProductId'], $requestData['enabled']);
        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
    public function displayAjaxSave()
    {
        $storeId = Tools::getValue('storeId');
        $requestData = Tools::getAllValues();
        $file = Tools::fileAttachment('logo');
        $result = AdminAPI::get()->payment($storeId)->save($this->createPaymentMethodRequest($storeId, $requestData, $file));
        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
    public function displayAjaxGetPaymentMethod()
    {
        $storeId = Tools::getValue('storeId');
        $methodId = Tools::getValue('methodId');
        $result = AdminAPI::get()->payment($storeId)->getPaymentMethod($methodId);
        OnlinePaymentsPrestaShopUtility::dieJson($result);
    }
    private function createPaymentMethodRequest(string $storeId, array $requestData, ?array $file = null) : PaymentMethodRequest
    {
        /** @var ActiveConnectionProvider $activeConnectionProvider */
        $activeConnectionProvider = ServiceRegister::getService(ActiveConnectionProvider::class);
        $mode = StoreContext::doWithStore($storeId, function () use($activeConnectionProvider) {
            return (string) $activeConnectionProvider->get()->getMode();
        });
        if ($file && !ImageHandler::saveImage($file['tmp_name'], $requestData['paymentProductId'], $storeId, $mode)) {
            OnlinePaymentsPrestaShopUtility::die400(['message' => 'Error occurred while saving a payment method image']);
        }
        $translations = \json_decode($requestData['name'], \true);
        $names = [];
        foreach ($translations as $translation) {
            $names[$translation['locale']] = $translation['value'];
        }
        $additionalData = [];
        $titles = [];
        $enableGroupCards = null;
        $instantPayment = null;
        $recurrenceType = null;
        $signatureType = null;
        $paymentProductId = null;
        $sessionTimeout = null;
        if (isset($requestData['additionalData'])) {
            $additionalData = \json_decode($requestData['additionalData'], \true);
            $instantPayment = $additionalData['instantPayment'] ?? null;
            $recurrenceType = $additionalData['recurrenceType'] ?? null;
            $signatureType = $additionalData['signatureType'] ?? null;
            $paymentProductId = $additionalData['paymentProductId'] ?? null;
            $sessionTimeout = $additionalData['sessionTimeout'] ?? null;
            $enableGroupCards = $additionalData['enableGroupCards'] ?? null;
            $vaultTitles = $additionalData['vaultTitleCollection'] ?? [];
            $titles = [];
            foreach ($vaultTitles as $vaultTitle) {
                $titles[$vaultTitle['locale']] = $vaultTitle['value'];
            }
        }
        $logo = '';
        if ($file) {
            $logo = ImageHandler::getImageUrl($requestData['paymentProductId'], $storeId, $mode);
        }
        if (!$logo && isset($additionalData['logo'])) {
            $logo = $additionalData['logo'];
        }
        return new PaymentMethodRequest($requestData['paymentProductId'], $names, \filter_var($requestData['enabled'], \FILTER_VALIDATE_BOOLEAN), $requestData['templateName'] ?? '', $titles, $logo, $enableGroupCards, null, $sessionTimeout, $paymentProductId, $recurrenceType, $signatureType, $instantPayment);
    }
}
