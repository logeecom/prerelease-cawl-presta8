<?php

namespace CAWL\OnlinePayments\Controllers\Concrete\Front;

use CAWL\OnlinePayments\Classes\Services\Checkout\CartProviderWithDeviceData;
use CAWL\OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Customer\Device;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
/**
 * Class PaymentModuleFrontController.
 *
 * @package OnlinePayments\Controllers\Concrete\Front
 */
class PaymentModuleFrontController extends \ModuleFrontController
{
    public function displayAjaxCreateHostedTokenizationSession()
    {
        $productId = \Tools::getValue('productId', null);
        $hostedTokenizationResponse = CheckoutAPI::get()->hostedTokenization((string) $this->context->shop->id)->crate(ServiceRegister::getService(CartProvider::class), $productId ? PaymentProductId::parse($productId) : null);
        if (!$hostedTokenizationResponse->isSuccessful()) {
            OnlinePaymentsPrestaShopUtility::dieJsonArray(['success' => \false]);
        }
        OnlinePaymentsPrestaShopUtility::dieJsonArray(['success' => \true, 'hostedTokenizationPageUrl' => $hostedTokenizationResponse->getHostedTokenization()->getUrl()]);
    }
    /**
     * @throws \Exception
     */
    public function displayAjaxCreatePayment()
    {
        $response = CheckoutAPI::get()->hostedTokenization((string) $this->context->shop->id)->pay(new PaymentRequest((string) \Tools::getValue('hostedTokenizationId', ''), new CartProviderWithDeviceData(ServiceRegister::getService(CartProvider::class), $this->getDeviceData()), $this->context->link->getModuleLink($this->module->name, 'redirect', ['action' => 'redirectReturnIframe']), \Tools::getValue('tokenId', null)));
        if (!$response->isSuccessful()) {
            OnlinePaymentsPrestaShopUtility::dieJsonArray(['success' => \false, 'message' => $this->module->l('An error occurred while processing the payment.', 'payment')]);
        }
        if ($response->isRedirectRequired()) {
            OnlinePaymentsPrestaShopUtility::dieJsonArray(['success' => \true, 'needRedirect' => \true, 'redirectUrl' => $response->getRedirectUrl()]);
        }
        OnlinePaymentsPrestaShopUtility::dieJsonArray(['success' => \true, 'needRedirect' => \true, 'redirectUrl' => $this->context->link->getModuleLink($this->module->name, 'redirect', ['action' => 'redirectReturnInternalIframe', 'paymentId' => (string) $response->getPaymentTransaction()->getPaymentId()])]);
    }
    /**
     * @return void
     */
    public function displayAjaxFormatSurchargeAmounts()
    {
        try {
            $return = ['success' => \true, 'formattedInitialAmount' => Amount::fromInt(\Tools::getValue('initialAmount'), Currency::fromIsoCode(\Tools::getValue('initialCurrency')))->getPriceInCurrencyUnits() . ' ' . \Tools::getValue('initialCurrency'), 'formattedSurchargeAmount' => Amount::fromInt(\Tools::getValue('surchargeAmount'), Currency::fromIsoCode(\Tools::getValue('surchargeCurrency')))->getPriceInCurrencyUnits() . ' ' . \Tools::getValue('surchargeCurrency'), 'formattedTotalAmount' => Amount::fromInt(\Tools::getValue('totalAmount'), Currency::fromIsoCode(\Tools::getValue('totalCurrency')))->getPriceInCurrencyUnits() . ' ' . \Tools::getValue('totalCurrency')];
        } catch (\Exception $e) {
            $return = ['success' => \false];
        }
        die(\json_encode($return));
    }
    private function getDeviceData() : ?Device
    {
        $ccForm = \Tools::getValue('ccForm');
        if (empty($ccForm)) {
            return null;
        }
        $ipaddress = $_SERVER['REMOTE_ADDR'];
        $customerConnections = $this->context->customer->getLastConnections();
        if (!empty($customerConnections)) {
            $connection = $customerConnections[0];
            $ipaddress = $connection['ipaddress'];
        }
        return new Device($_SERVER['HTTP_ACCEPT'], $_SERVER['HTTP_USER_AGENT'], $ipaddress, (int) $ccForm['colorDepth'], (string) $ccForm['screenHeight'], (string) $ccForm['screenWidth'], (string) $ccForm['timezoneOffsetUtcMinutes'], (bool) $ccForm['javaEnabled']);
    }
}
