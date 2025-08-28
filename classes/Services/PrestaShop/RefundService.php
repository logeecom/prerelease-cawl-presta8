<?php

namespace OnlinePayments\Classes\Services\PrestaShop;

use Exception;
use OnlinePayments\Core\Bootstrap\ApiFacades\Order\OrderAPI\OrderAPI;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Exceptions\InvalidCurrencyCode;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
use OnlinePayments\Core\BusinessLogic\Domain\Refund\RefundRequest;

/**
 * Class RefundService
 *
 * @package OnlinePayments\Classes\Services\PrestaShop
 */
class RefundService
{
    /** @var string File name for translation contextualization */
    public const FILE_NAME = 'RefundService';

    private $module;
    private int $storeId;

    /**
     * RefundService constructor.
     */
    public function __construct(string $moduleName, int $storeId)
    {
        $this->module = \Module::getInstanceByName($moduleName);
        $this->storeId = $storeId;
    }

    public function handleFromExtension(array $transaction): string
    {
        try {
            $refundResponse = OrderAPI::get()->refund($this->storeId)->handle(new RefundRequest(
                PaymentId::parse($transaction['id']),
                Amount::fromFloat(
                    $transaction['amountToRefund'],
                    Currency::fromIsoCode($transaction['currencyCode'])
                ),
                $transaction['idOrder']
            ));

            if (!$refundResponse->isSuccessful()) {
                $errorMessage = 'Refund creation failed!';
                if ($refundResponse->toArray() && isset($refundResponse->toArray()['errorMessage'])) {
                    $errorMessage .= ' Reason: ' . $refundResponse->toArray()['errorMessage'];
                }

                return $this->module->l($errorMessage, self::FILE_NAME);
            }
        } catch (Exception $e) {
            return $this->module->l($e->getMessage(), self::FILE_NAME);
        }

        $refund = $refundResponse->toArray();
        if (!in_array($refund['statusCode'], array_merge(
            StatusCode::REFUND_STATUS_CODES, StatusCode::REFUND_REQUESTED_STATUS_CODES))) {
            return $this->module->l("Refund of funds failed with status {$refund['status']}", self::FILE_NAME);
        }

        return '';
    }

    public function handleStandard(string $cartId, \Order $order): string
    {
        $transaction = CheckoutAPI::get()->payment($this->storeId)->getPaymentTransaction($cartId);

        if (!$transaction->isSuccessful()) {
            $errorMessage = 'Transaction fetching failed!';
            if ($transaction->toArray() && isset($transaction->toArray()['errorMessage'])) {
                $errorMessage .= ' Reason: ' . $transaction->toArray()['errorMessage'];
            }

            return $this->module->l($errorMessage, self::FILE_NAME);
        }

        try {
            $refundResponse = OrderAPI::get()->refund($this->storeId)->handle(new RefundRequest(
                $transaction->getPaymentTransaction()->getPaymentId(),
                $this->getRefundedAmount($order),
                $cartId
            ));

            if (!$refundResponse->isSuccessful()) {
                $errorMessage = 'Refund failed on Worldline!';
                if ($refundResponse->toArray() && isset($refundResponse->toArray()['errorMessage'])) {
                    $errorMessage .= ' Reason: ' . $refundResponse->toArray()['errorMessage'];
                }

                return $this->module->l($errorMessage, self::FILE_NAME);
            }
        } catch (Exception $e) {
            return $this->module->l($e->getMessage(), self::FILE_NAME);
        }

        return '';
    }

    /**
     * @param \Order $order
     *
     * @return Amount
     * @throws InvalidCurrencyCode
     */
    private function getRefundedAmount(\Order $order): Amount
    {
        /** @var \OrderSlip $lastOrderSlip */
        $lastOrderSlip = $order->getOrderSlipsCollection()->getLast();
        $amount = $lastOrderSlip->total_products_tax_incl + $lastOrderSlip->shipping_cost_amount;

        $shopCurrency = new \Currency($order->id_currency);
        $currency = Currency::fromIsoCode($shopCurrency->iso_code);

        return Amount::fromFloat($amount, $currency);
    }
}