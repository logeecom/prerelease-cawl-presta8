<?php

namespace CAWL\OnlinePayments\Classes\Services\PrestaShop;

use Exception;
use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\OrderAPI\OrderAPI;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Exceptions\InvalidCurrencyCode;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Refund\RefundRequest;
/**
 * Class RefundService
 *
 * @package OnlinePayments\Classes\Services\PrestaShop
 */
class RefundService
{
    /** @var string File name for translation contextualization */
    public const FILE_NAME = 'RefundService';
    /** @var OnlinePaymentsModule */
    private $module;
    private int $storeId;
    /**
     * RefundService constructor.
     */
    public function __construct(OnlinePaymentsModule $module, int $storeId)
    {
        $this->module = $module;
        $this->storeId = $storeId;
    }
    public function handleFromExtension(array $transaction) : string
    {
        try {
            $refundResponse = OrderAPI::get()->refund($this->storeId)->handle(new RefundRequest(PaymentId::parse($transaction['id']), Amount::fromFloat($transaction['amountToRefund'], Currency::fromIsoCode($transaction['currencyCode'])), $transaction['idOrder']));
            if (!$refundResponse->isSuccessful()) {
                $errorMessage = 'Refund creation failed on ' . $this->module->getBrand()->getName() . '!';
                return $this->module->l($errorMessage, self::FILE_NAME);
            }
        } catch (Exception $e) {
            return $this->module->l('Unexpected error occurred during refund.', self::FILE_NAME);
        }
        $refund = $refundResponse->toArray();
        if (!\in_array($refund['statusCode'], \array_merge(StatusCode::REFUND_STATUS_CODES, StatusCode::REFUND_REQUESTED_STATUS_CODES))) {
            return $this->module->l("Refund of funds failed. Payment is not in Refund or Refund requested status.", self::FILE_NAME);
        }
        return '';
    }
    public function handleStandard(string $cartId, \Order $order) : string
    {
        $transaction = CheckoutAPI::get()->payment($this->storeId)->getPaymentTransaction($cartId);
        if (!$transaction->isSuccessful()) {
            $errorMessage = 'Transaction fetching failed!';
            return $this->module->l($errorMessage, self::FILE_NAME);
        }
        try {
            $refundResponse = OrderAPI::get()->refund($this->storeId)->handle(new RefundRequest($transaction->getPaymentTransaction()->getPaymentId(), $this->getRefundedAmount($order), $cartId));
            if (!$refundResponse->isSuccessful()) {
                $errorMessage = 'Refund creation failed on ' . $this->module->getBrand()->getName() . '!';
                return $this->module->l($errorMessage, self::FILE_NAME);
            }
        } catch (Exception $e) {
            return $this->module->l('Unexpected error occurred during refund', self::FILE_NAME);
        }
        return '';
    }
    /**
     * @param \Order $order
     *
     * @return Amount
     * @throws InvalidCurrencyCode
     */
    private function getRefundedAmount(\Order $order) : Amount
    {
        /** @var \OrderSlip $lastOrderSlip */
        $lastOrderSlip = $order->getOrderSlipsCollection()->getLast();
        $amount = $lastOrderSlip->total_products_tax_incl + $lastOrderSlip->shipping_cost_amount;
        $shopCurrency = new \Currency($order->id_currency);
        $currency = Currency::fromIsoCode($shopCurrency->iso_code);
        return Amount::fromFloat($amount, $currency);
    }
}
