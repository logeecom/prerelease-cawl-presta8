<?php

namespace CAWL\OnlinePayments\Classes\Services\PrestaShop;

use Exception;
use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
use CAWL\OnlinePayments\Classes\Utility\SessionService;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\OrderAPI\OrderAPI;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Exceptions\InvalidCurrencyCode;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
/**
 * Class CancelService
 *
 * @package OnlinePayments\Classes\Services\PrestaShop
 */
class CancelService
{
    /** @var string File name for translation contextualization */
    public const FILE_NAME = 'CancelService';
    /** @var OnlinePaymentsModule */
    private $module;
    private int $storeId;
    /**
     * CancelService constructor.
     */
    public function __construct(OnlinePaymentsModule $module, int $storeId)
    {
        $this->module = $module;
        $this->storeId = $storeId;
    }
    public function handle(string $cartId, \Order $order) : void
    {
        $transaction = CheckoutAPI::get()->payment($this->storeId)->getPaymentTransaction($cartId);
        if (!$transaction->isSuccessful()) {
            $errorMessage = 'Transaction fetching failed!';
            self::setErrorMessage($this->module->l($errorMessage, self::FILE_NAME));
        }
        $cancelResponse = null;
        try {
            $cancelResponse = OrderAPI::get()->cancel($this->storeId)->handle(new CancelRequest($transaction->getPaymentTransaction()->getPaymentId(), $this->getCancelledAmount($order)));
            if (!$cancelResponse->isSuccessful()) {
                $errorMessage = 'Cancel creation failed on ' . $this->module->getBrand()->getName() . '!';
                self::setErrorMessage($this->module->l($errorMessage, self::FILE_NAME));
            }
        } catch (Exception $e) {
            self::setErrorMessage($this->module->l('Unexpected error occurred during refund.', self::FILE_NAME));
        }
        $cancel = $cancelResponse ? $cancelResponse->toArray() : [];
        if (!\in_array($cancel['statusCode'], StatusCode::CANCEL_STATUS_CODES)) {
            self::setErrorMessage($this->module->l("Cancel of funds failed. Payment is not in Canceled status.", self::FILE_NAME));
        }
        self::setSuccessMessage($this->module->l('Cancellation request successfully sent.', self::FILE_NAME));
    }
    public function handleFromExtension(array $transaction) : string
    {
        $order = new \Order((int) $transaction['idOrder']);
        if (!\Validate::isLoadedObject($order)) {
            return $this->module->l('Unexpected error occurred during cancellation.', self::FILE_NAME);
        }
        try {
            $cancelResponse = OrderAPI::get()->cancel($this->storeId)->handle(new CancelRequest(PaymentId::parse($transaction['id']), Amount::fromFloat($transaction['amountToCancel'], Currency::fromIsoCode($transaction['currencyCode']))));
            if (!$cancelResponse->isSuccessful()) {
                return \sprintf($this->module->l('Cancel creation failed on %s!', self::FILE_NAME), $this->module->getBrand()->getName());
            }
        } catch (Exception $e) {
            return $this->module->l('Unexpected error occurred during cancellation.', self::FILE_NAME);
        }
        $cancel = $cancelResponse->toArray();
        if (!\in_array($cancel['statusCode'], StatusCode::CANCEL_STATUS_CODES)) {
            return $this->module->l("Cancel of funds failed. Payment is not in Canceled status.", self::FILE_NAME);
        }
        return '';
    }
    /**
     * @param \Order $order
     *
     * @return Amount
     * @throws InvalidCurrencyCode
     */
    private function getCancelledAmount(\Order $order) : Amount
    {
        $amount = $order->total_paid;
        $shopCurrency = new \Currency($order->id_currency);
        $currency = Currency::fromIsoCode($shopCurrency->iso_code);
        return Amount::fromFloat($amount, $currency);
    }
    /**
     * @param string $message
     *
     * @return void
     */
    private static function setSuccessMessage(string $message) : void
    {
        SessionService::set('successMessage', $message);
    }
    /**
     * @param string $message
     *
     * @return void
     */
    private static function setErrorMessage(string $message) : void
    {
        SessionService::set('errorMessage', $message);
    }
}
