<?php

namespace OnlinePayments\Classes\Services\PrestaShop;

use Exception;
use OnlinePayments\Classes\Utility\SessionService;
use OnlinePayments\Core\Bootstrap\ApiFacades\Order\OrderAPI\OrderAPI;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelRequest;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Exceptions\InvalidCurrencyCode;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;

/**
 * Class CancelService
 *
 * @package OnlinePayments\Classes\Services\PrestaShop
 */
class CancelService
{
    /** @var string File name for translation contextualization */
    public const FILE_NAME = 'CancelService';

    private $module;
    private int $storeId;

    /**
     * CancelService constructor.
     */
    public function __construct(string $moduleName, int $storeId)
    {
        $this->module = \Module::getInstanceByName($moduleName);
        $this->storeId = $storeId;
    }

    public function handle(string $cartId, \Order $order): void
    {
        $transaction = CheckoutAPI::get()->payment($this->storeId)->getPaymentTransaction($cartId);

        if (!$transaction->isSuccessful()) {
            $errorMessage = 'Transaction fetching failed!';
            if ($transaction->toArray() && isset($transaction->toArray()['errorMessage'])) {
                $errorMessage .= ' Reason: ' . $transaction->toArray()['errorMessage'];
            }

            self::setErrorMessage($this->module->l($errorMessage, self::FILE_NAME));
        }

        try {
            $cancelResponse = OrderAPI::get()->cancel($this->storeId)->handle(new CancelRequest(
                $transaction->getPaymentTransaction()->getPaymentId(),
                $this->getCancelledAmount($order)
            ));

            if (!$cancelResponse->isSuccessful()) {
                $errorMessage = 'Cancel creation failed!';
                if ($cancelResponse->toArray() && isset($cancelResponse->toArray()['errorMessage'])) {
                    $errorMessage .= ' Reason: ' . $cancelResponse->toArray()['errorMessage'];
                }

                self::setErrorMessage($this->module->l($errorMessage, self::FILE_NAME));
            }
        } catch (Exception $e) {
            self::setErrorMessage($this->module->l($e->getMessage(), self::FILE_NAME));
        }

        $cancel = $cancelResponse->toArray();
        if (!in_array($cancel['statusCode'], StatusCode::CANCEL_STATUS_CODES)) {
            self::setErrorMessage(
                $this->module->l("Cancel of funds failed with status {$cancel['status']}",
                    self::FILE_NAME)
            );
        }

        self::setSuccessMessage(
            $this->module->l('Cancellation request successfully sent to the Worldline.',
                self::FILE_NAME)
        );
    }

    public function handleFromExtension(array $transaction): string
    {
        try {
            $cancelResponse = OrderAPI::get()->cancel($this->storeId)->handle(new CancelRequest(
                PaymentId::parse($transaction['id']),
                Amount::fromFloat(
                    $transaction['amountToCancel'],
                    Currency::fromIsoCode($transaction['currencyCode'])
                )
            ));

            if (!$cancelResponse->isSuccessful()) {
                $errorMessage = 'Cancel creation failed!';
                if ($cancelResponse->toArray() && isset($cancelResponse->toArray()['errorMessage'])) {
                    $errorMessage .= ' Reason: ' . $cancelResponse->toArray()['errorMessage'];
                }

                return $this->module->l($errorMessage, self::FILE_NAME);
            }
        } catch (Exception $e) {
            return $this->module->l($e->getMessage(), self::FILE_NAME);
        }

        $cancel = $cancelResponse->toArray();
        if (!in_array($cancel['statusCode'], StatusCode::CANCEL_STATUS_CODES)) {
            return $this->module->l("Cancel of funds failed with status {$cancel['status']}",self::FILE_NAME);
        }

        return '';
    }

    /**
     * @param \Order $order
     *
     * @return Amount
     * @throws InvalidCurrencyCode
     */
    private function getCancelledAmount(\Order $order): Amount
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
    private static function setSuccessMessage(string $message): void
    {
        SessionService::set('successMessage', $message);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private static function setErrorMessage(string $message): void
    {
        SessionService::set('errorMessage', $message);
    }
}