<?php

namespace CAWL\OnlinePayments\Classes\Services\PrestaShop;

use Exception;
use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
use CAWL\OnlinePayments\Classes\Services\OrderStatusMappingService;
use CAWL\OnlinePayments\Classes\Utility\Url;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\OrderAPI\OrderAPI;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
/**
 * Class OrderService
 *
 * @package OnlinePayments\Classes\Services\PrestaShop
 */
class OrderService
{
    /** @var string File name for translation contextualization */
    public const FILE_NAME = 'OrderService';
    /** @var OnlinePaymentsModule */
    private $module;
    private int $storeId;
    /**
     * OrderService constructor.
     */
    public function __construct(OnlinePaymentsModule $module, int $storeId)
    {
        $this->module = $module;
        $this->storeId = $storeId;
    }
    /**
     * @param string $orderId
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function getDetails(string $orderId) : array
    {
        $order = new \Order((int) $orderId);
        $errorMessages = [];
        try {
            $orderData = $this->getOrderData($orderId);
        } catch (Exception $exception) {
            $orderData = ['orderId' => $orderId];
            $errorMessages[] = $exception->getMessage();
        }
        try {
            $paymentLinkData = $this->getPaymentLinkData($order, $orderData);
        } catch (Exception $exception) {
            $paymentLinkData = ['displayButton' => \false];
            $errorMessages[] = $exception->getMessage();
        }
        $settingsData = $this->getOrderSettingsData();
        return ['transactionData' => $orderData, 'settingsData' => $settingsData, 'paymentLinkData' => $paymentLinkData, 'errorMessages' => $errorMessages];
    }
    private function getOrderSettingsData() : array
    {
        return ['moduleName' => $this->module->name, 'brandCode' => $this->module->getBrand()->getCode(), 'brandName' => $this->module->getBrand()->getName(), 'pathImg' => \sprintf(__PS_BASE_URI__ . 'modules/%s/views/assets/images/', $this->module->name), 'transactionUrl' => Url::getAdminController('Transaction')];
    }
    private function getPaymentLinkData(\Order $order, array $orderData) : array
    {
        if (isset($orderData['payment'])) {
            return ['displayButton' => \false];
        }
        $generalSettingsResponse = AdminAPI::get()->generalSettings($this->storeId)->getGeneralSettings();
        if (!$generalSettingsResponse->isSuccessful()) {
            $errorMessage = "General settings fetch failed!";
            throw new \Exception($this->module->l($errorMessage, self::FILE_NAME));
        }
        $generalSettings = $generalSettingsResponse->toArray();
        $payByLinkEnabled = \array_key_exists('payByLinkSettings', $generalSettings) && $generalSettings['payByLinkSettings']['enabled'];
        $shouldDisplayPayByLinkButton = $payByLinkEnabled && ($order->current_state == OrderStatusMappingService::PRESTA_CANCELED_ID || $order->current_state == OrderStatusMappingService::PRESTA_PAYMENT_ERROR_ID || $order->current_state == OrderStatusMappingService::PRESTA_ON_BACKORDER_ID);
        $paymentLinkData = ['displayButton' => $shouldDisplayPayByLinkButton];
        if ($payByLinkEnabled) {
            $paymentLinkResponse = AdminAPI::get()->paymentLinks($this->storeId)->get(\Cart::getCartIdByOrderId($order->id));
            if (!$paymentLinkResponse->isSuccessful()) {
                $errorMessage = "Payment link fetching failed!";
                throw new \Exception($this->module->l($errorMessage, self::FILE_NAME));
            }
            if ($paymentLinkResponse->getRedirectUrl()) {
                $paymentLinkData['redirectUrl'] = $paymentLinkResponse->getRedirectUrl();
            }
        }
        return $paymentLinkData;
    }
    private function getOrderData(string $orderId) : array
    {
        $order = new \Order((int) $orderId);
        if ($order->module !== $this->module->name) {
            return ['orderId' => $orderId];
        }
        $cartId = \Cart::getCartIdByOrderId($orderId);
        $orderDetailsResponse = OrderAPI::get()->orders($this->storeId)->getDetails($cartId);
        if (!$orderDetailsResponse->isSuccessful()) {
            $errorMessage = "Order details fetch failed!";
            throw new \Exception($this->module->l($errorMessage, self::FILE_NAME));
        }
        $orderDetails = $orderDetailsResponse->getOrderDetails();
        $currency = $orderDetails->getAmount()->getCurrency();
        $currencyIsoCode = $currency->getIsoCode();
        $decimals = $currency->getMinorUnits();
        $psOrderAmountMatch = \true;
        if ($order->total_paid_tax_incl) {
            $psAmount = Amount::fromFloat($order->total_paid_tax_incl, Currency::fromIsoCode($currencyIsoCode));
            $psOrderAmountMatch = $orderDetails->getAmount()->getValue() === $psAmount->getValue();
        }
        $orderHasSurcharge = \false;
        $orderSurchargeAmount = Amount::fromInt(0, $currency);
        foreach ($orderDetails->getPayments() as $payment) {
            if ($payment->getSurcharge()) {
                $orderHasSurcharge = \true;
                $orderSurchargeAmount = $orderSurchargeAmount->plus($payment->getSurcharge());
            }
        }
        $payments = $orderDetails->getPayments();
        $paymentId = \reset($payments)->getId()->getTransactionId();
        return ['orderId' => $orderId, 'payment' => ['id' => $paymentId, 'currencyCode' => $currencyIsoCode, 'hasSurcharge' => $orderHasSurcharge, 'surchargeAmount' => $orderSurchargeAmount->getPriceInCurrencyUnits(), 'amount' => $orderDetails->getAmount()->getPriceInCurrencyUnits(), 'amountWithoutSurcharge' => $orderDetails->getAmount()->minus($orderSurchargeAmount)->getPriceInCurrencyUnits()], 'payments' => \array_map(function ($payment) {
            return ['amount' => $payment->getAmount()->getPriceInCurrencyUnits(), 'hasSurcharge' => $payment->getSurcharge() && $payment->getSurcharge()->getValue() !== 0, 'surchargeAmount' => $payment->getSurcharge() ? $payment->getSurcharge()->getPriceInCurrencyUnits() : 0, 'amountWithoutSurcharge' => $payment->getAmount()->getPriceInCurrencyUnits(), 'currencyCode' => $payment->getAmount()->getCurrency()->getIsoCode(), 'id' => (string) $payment->getId(), 'status' => $payment->getStatus(), 'productId' => $payment->getPaymentMethodId(), 'productName' => $payment->getPaymentMethodName(), 'fraudResult' => $payment->getFraudResult() ?? '', 'liability' => $payment->getLiability() ?? '', 'exemptionType' => $payment->getExemptionType() ?? ''];
        }, $orderDetails->getPayments()), 'psOrderAmountMatch' => $psOrderAmountMatch, 'errors' => \array_map(function ($error) {
            return ['id' => $error->getId(), 'code' => $error->getErrorCode()];
        }, $orderDetails->getErrors()), 'actions' => ['isAuthorized' => $orderDetails->getCapture()->isPossible(), 'isCancellable' => $orderDetails->getCancel()->isPossible(), 'isRefundable' => $orderDetails->getRefund()->isPossible()], 'refunds' => ['refundableAmount' => \number_format($orderDetails->getRefund()->getAvailable()->getPriceInCurrencyUnits(), $decimals, '.', ''), 'totalPendingRefund' => $orderDetails->getRefund()->getPending()->getPriceInCurrencyUnits() ?: "0.00", 'totalRefunded' => $orderDetails->getRefund()->getDone()->getPriceInCurrencyUnits() ?: "0.00"], 'captures' => ['capturableAmount' => \number_format($orderDetails->getCapture()->getAvailable()->getPriceInCurrencyUnits(), $decimals, '.', ''), 'totalPendingCapture' => $orderDetails->getCapture()->getPending()->getPriceInCurrencyUnits() ?: "0.00", 'totalCaptured' => $orderDetails->getCapture()->getDone()->getPriceInCurrencyUnits() ?: "0.00"], 'cancels' => ['cancellableAmount' => \number_format($orderDetails->getCancel()->getAvailable()->getPriceInCurrencyUnits(), $decimals, '.', ''), 'totalPendingCancel' => $orderDetails->getCancel()->getPending()->getPriceInCurrencyUnits() ?: "0.00", 'totalCancelled' => $orderDetails->getCancel()->getDone()->getPriceInCurrencyUnits() ?: "0.00"]];
    }
}
