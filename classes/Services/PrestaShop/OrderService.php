<?php

namespace OnlinePayments\Classes\Services\PrestaShop;

use Exception;
use OnlinePayments\Classes\Services\OrderStatusMappingService;
use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use OnlinePayments\Core\Bootstrap\ApiFacades\Order\OrderAPI\OrderAPI;
use OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use WorldlineOP\PrestaShop\Utils\Tools;

/**
 * Class OrderService
 *
 * @package OnlinePayments\Classes\Services\PrestaShop
 */
class OrderService
{
    /** @var string File name for translation contextualization */
    public const FILE_NAME = 'OrderService';

    private $module;
    private int $storeId;

    /**
     * OrderService constructor.
     */
    public function __construct(string $moduleName, int $storeId)
    {
        $this->module = \Module::getInstanceByName($moduleName);
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
    public function getDetails(string $orderId): array
    {
        $order = new \Order((int)$orderId);

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
            $paymentLinkData = ['display' => false];
            $errorMessages[] = $exception->getMessage();
        }

        $settingsData = $this->getOrderSettingsData();

        return [
            'transactionData' => $orderData,
            'settingsData' => $settingsData,
            'paymentLinkData' => $paymentLinkData,
            'errorMessages' => $errorMessages,
        ];
    }

    private function getOrderSettingsData(): array
    {
        /** @var ActiveBrandProviderInterface $provider */
        $provider = ServiceRegister::getService(ActiveBrandProviderInterface::class);

        return [
            'moduleName' => $this->module->name,
            'brandCode' => $provider->getActiveBrand()->getCode(),
            'brandName' => $provider->getActiveBrand()->getName(),
            'pathImg' => sprintf(__PS_BASE_URI__ . 'modules/%s/views/assets/images/', $this->module->name),
        ];
    }

    private function getPaymentLinkData(\Order $order, array $worldlineOrderData): array
    {
        if (isset($worldlineOrderData['payment'])) {
            return [
                'display' => false
            ];
        }

        $generalSettingsResponse = AdminAPI::get()->generalSettings($this->storeId)->getGeneralSettings();

        if (!$generalSettingsResponse->isSuccessful()) {
            $errorMessage = "Module: {$this->module->name}: General settings fetch failed!";
            if ($generalSettingsResponse->toArray() && isset($generalSettingsResponse->toArray()['errorMessage'])) {
                $errorMessage .= ' Reason: ' . $generalSettingsResponse->toArray()['errorMessage'];
            }

            throw new \Exception($this->module->l($errorMessage, self::FILE_NAME));
        }

        $generalSettings = $generalSettingsResponse->toArray();

        $payByLinkEnabled = array_key_exists('payByLinkSettings', $generalSettings)
            && $generalSettings['payByLinkSettings']['enabled'];

        $shouldDisplayPayByLink = $payByLinkEnabled && (
                $order->current_state == OrderStatusMappingService::PRESTA_CANCELED_ID ||
                $order->current_state == OrderStatusMappingService::PRESTA_PAYMENT_ERROR_ID ||
                $order->current_state == OrderStatusMappingService::PRESTA_ON_BACKORDER_ID);

        $paymentLinkData = [
            'display' => $shouldDisplayPayByLink
        ];

        if ($shouldDisplayPayByLink) {
            $paymentLinkResponse = AdminAPI::get()->paymentLinks($this->storeId)->get(
                \Cart::getCartIdByOrderId($order->id)
            );

            if (!$paymentLinkResponse->isSuccessful()) {
                $errorMessage = "Module: {$this->module->name}: Payment link fetching failed!";
                if ($paymentLinkResponse->toArray() && isset($paymentLinkResponse->toArray()['errorMessage'])) {
                    $errorMessage .= ' Reason: ' . $paymentLinkResponse->toArray()['errorMessage'];
                }

                throw new \Exception($this->module->l($errorMessage, self::FILE_NAME));
            }

            if ($paymentLinkResponse->getRedirectUrl()) {
                $paymentLinkData['redirectUrl'] = $paymentLinkResponse->getRedirectUrl();
            }
        }

        return $paymentLinkData;
    }

    private function getOrderData(string $orderId): array
    {
        $cartId = \Cart::getCartIdByOrderId($orderId);
        $orderDetailsResponse = OrderAPI::get()->orders($this->storeId)->getDetails($cartId);

        if (!$orderDetailsResponse->isSuccessful()) {
            $errorMessage = "Module: {$this->module->name}: Order details fetch failed!";
            if ($orderDetailsResponse->toArray() && isset($orderDetailsResponse->toArray()['errorMessage'])) {
                $errorMessage .= ' Reason: ' . $orderDetailsResponse->toArray()['errorMessage'];
            }

            throw new \Exception($this->module->l($errorMessage, self::FILE_NAME));
        }

        $orderDetails = $orderDetailsResponse->getOrderDetails();

        $currency = $orderDetails->getAmount()->getCurrency();
        $currencyIsoCode = $currency->getIsoCode();
        $decimals = $currency->getMinorUnits();

        $order = new \Order((int) $orderId);
        $psOrderAmountMatch = true;
        if ($order->total_paid_tax_incl) {
            $worldlineAmount = $orderDetails->getAmount()->getValue();
            $psAmount = (int) Tools::getRoundedAmountInCents($order->total_paid_tax_incl, $currencyIsoCode);
            $psOrderAmountMatch = ($worldlineAmount === $psAmount);
        }

        $orderHasSurcharge = false;

        $orderSurchargeAmount = Amount::fromInt(0, $currency);
        foreach ($orderDetails->getPayments() as $payment) {
            if ($payment->getSurcharge()) {
                $orderHasSurcharge = true;
                $orderSurchargeAmount = $orderSurchargeAmount->plus($payment->getSurcharge());
            }
        }

        $payments = $orderDetails->getPayments();
        $paymentId = reset($payments)->getId()->getTransactionId();

        return [
            'orderId' => $orderId,
            'payment' => [
                'id' => $paymentId,
                'currencyCode' => $currencyIsoCode,
                'hasSurcharge' => $orderHasSurcharge,
                'surchargeAmount' => $orderSurchargeAmount->getPriceInCurrencyUnits(),
                'amount' => $orderDetails->getAmount()->getPriceInCurrencyUnits(),
                'amountWithoutSurcharge' =>
                    $orderDetails->getAmount()->minus($orderSurchargeAmount)->getPriceInCurrencyUnits(),
            ],
            'payments' => array_map(function ($payment) {
                return [
                    'amount' => $payment->getAmount()->getPriceInCurrencyUnits(),
                    'hasSurcharge' => $payment->getSurcharge() && $payment->getSurcharge()->getValue() !== 0,
                    'surchargeAmount' => $payment->getSurcharge() ? $payment->getSurcharge()->getPriceInCurrencyUnits() : 0,
                    'amountWithoutSurcharge' => $payment->getAmount()->getPriceInCurrencyUnits(),
                    'currencyCode' => $payment->getAmount()->getCurrency()->getIsoCode(),
                    'id' => (string)$payment->getId(),
                    'status' => $payment->getStatus(),
                    'productId' => $payment->getPaymentMethodId(),
                    'productName' => $payment->getPaymentMethodName(),
                    'fraudResult' => $payment->getFraudResult() ?? '',
                    'liability' => $payment->getLiability() ?? '',
                    'exemptionType' => $payment->getExemptionType() ?? '',
                ];
            }, $orderDetails->getPayments()),
            'psOrderAmountMatch' => $psOrderAmountMatch,
            'errors' => array_map(function ($error) {
                return [
                    'id' => $error->getId(),
                    'code' => $error->getErrorCode()
                ];
            }, $orderDetails->getErrors()),
            'actions' => [
                'isAuthorized' => $orderDetails->getCapture()->isPossible(),
                'isCancellable' => $orderDetails->getCancel()->isPossible(),
                'isRefundable' => $orderDetails->getRefund()->isPossible(),
            ],
            'refunds' => [
                'refundableAmount' => number_format(
                    $orderDetails->getRefund()->getAvailable()->getPriceInCurrencyUnits(),
                    $decimals,
                    '.',
                    ''
                ),
                'totalPendingRefund' => $orderDetails->getRefund()->getPending()->getPriceInCurrencyUnits(),
                'totalRefunded' => $orderDetails->getRefund()->getDone()->getPriceInCurrencyUnits()
            ],
            'captures' => [
                'capturableAmount' => number_format(
                    $orderDetails->getCapture()->getAvailable()->getPriceInCurrencyUnits(),
                    $decimals,
                    '.',
                    ''
                ),
                'totalPendingCapture' => $orderDetails->getCapture()->getPending()->getPriceInCurrencyUnits(),
                'totalCaptured' => $orderDetails->getCapture()->getDone()->getPriceInCurrencyUnits()
            ],
            'cancels' => [
                'cancellableAmount' => number_format(
                    $orderDetails->getCancel()->getAvailable()->getPriceInCurrencyUnits(),
                    $decimals,
                    '.',
                    ''
                ),
                'totalPendingCancel' => $orderDetails->getCancel()->getPending()->getPriceInCurrencyUnits(),
                'totalCancelled' => $orderDetails->getCancel()->getDone()->getPriceInCurrencyUnits()
            ],
        ];
    }
}