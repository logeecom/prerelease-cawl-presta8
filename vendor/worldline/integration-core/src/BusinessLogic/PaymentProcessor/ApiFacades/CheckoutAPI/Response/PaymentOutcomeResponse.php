<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ApiFacades\Response\Response;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses\WaitPaymentOutcome;
/**
 * Class PaymentOutcomeResponse.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response
 * @internal
 */
class PaymentOutcomeResponse extends Response
{
    private WaitPaymentOutcome $paymentOutcome;
    public function __construct(WaitPaymentOutcome $paymentOutcome)
    {
        $this->paymentOutcome = $paymentOutcome;
    }
    public function toArray() : array
    {
        return ['isWaiting' => $this->isWaiting()];
    }
    public function isWaiting() : bool
    {
        return $this->paymentOutcome->isWaiting();
    }
    public function getPaymentTransaction() : PaymentTransaction
    {
        return $this->paymentOutcome->getPaymentTransaction();
    }
}
