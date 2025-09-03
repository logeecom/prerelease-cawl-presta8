<?php

namespace OnlinePayments\Controllers\Concrete\Front;

use OnlinePayments\Classes\Services\Checkout\CartProviderWithDeviceData;
use OnlinePayments\Classes\Utility\OnlinePaymentsPrestaShopUtility;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Customer\Device;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentRequest;
use OnlinePayments\Core\Infrastructure\ServiceRegister;

/**
 * Class PaymentModuleFrontController.
 *
 * @package OnlinePayments\Controllers\Concrete\Front
 */
class PaymentModuleFrontController extends \ModuleFrontController
{
    /**
     * @throws \Exception
     */
    public function displayAjaxCreatePayment()
    {
        $response = CheckoutAPI::get()
            ->hostedTokenization((string)$this->context->shop->id)
            ->pay(new PaymentRequest(
                (string)\Tools::getValue('hostedTokenizationId', ''),
                new CartProviderWithDeviceData(ServiceRegister::getService(CartProvider::class), $this->getDeviceData()),
                $this->context->link->getModuleLink($this->module->name, 'redirect', ['action' => 'redirectReturnIframe']),
                \Tools::getValue('tokenId', null)
            ));

        if (!$response->isSuccessful()) {
            OnlinePaymentsPrestaShopUtility::dieJsonArray([
                'success' => false,
                'message' => $this->module->l('An error occurred while processing the payment.', 'payment'),
            ]);
        }

        if ($response->isRedirectRequired()) {
            OnlinePaymentsPrestaShopUtility::dieJsonArray([
                'success' => true,
                'needRedirect' => true,
                'redirectUrl' => $response->getRedirectUrl(),
            ]);
        }

        OnlinePaymentsPrestaShopUtility::dieJsonArray([
            'success' => true,
            'needRedirect' => true,
            'redirectUrl' => $this->context->link->getModuleLink(
                $this->module->name,
                'redirect',
                ['action' => 'redirectReturnInternalIframe', 'paymentId' => (string)$response->getPaymentTransaction()->getPaymentId()]
            ),
        ]);
    }

    /**
     * @return void
     */
    public function displayAjaxFormatSurchargeAmounts()
    {
        try {
            $return = [
                'success' => true,
                'formattedInitialAmount' => Amount::fromInt(
                    \Tools::getValue('initialAmount'),
                    Currency::fromIsoCode(\Tools::getValue('initialCurrency'))
                )->getPriceInCurrencyUnits() . ' ' . \Tools::getValue('initialCurrency'),
                'formattedSurchargeAmount' => Amount::fromInt(
                    \Tools::getValue('surchargeAmount'),
                    Currency::fromIsoCode(\Tools::getValue('surchargeCurrency'))
                )->getPriceInCurrencyUnits() . ' ' . \Tools::getValue('surchargeCurrency'),
                'formattedTotalAmount' => Amount::fromInt(
                    \Tools::getValue('totalAmount'),
                    Currency::fromIsoCode(\Tools::getValue('totalCurrency'))
                )->getPriceInCurrencyUnits() . ' ' . \Tools::getValue('totalCurrency'),
            ];
        } catch (\Exception $e) {
            $return = [
                'success' => false,
            ];
        }

        die(json_encode($return));
    }

    private function getDeviceData(): ?Device
    {
        $ccForm = \Tools::getValue('ccForm');
        if (empty($ccForm)) {
            return null;
        }

        $ipaddress = $_SERVER['REMOTE_ADDR'];
        $customerConnections = $this->context->customer->getLastConnections();
        if (!empty($customerConnections)) {
            $connection = $customerConnections[0];
            $ipaddress =  $connection['ipaddress'];
        }

        return new Device(
            $_SERVER['HTTP_ACCEPT'],
            $_SERVER['HTTP_USER_AGENT'],
            $ipaddress,
            (int)$ccForm['colorDepth'],
            (string)$ccForm['screenHeight'],
            (string)$ccForm['screenWidth'],
            (string)$ccForm['timezoneOffsetUtcMinutes'],
            (bool)$ccForm['javaEnabled'],
        );
    }
}
