<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
/**
 * Class WaitPaymentOutcome.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses
 * @internal
 */
class WaitPaymentOutcome
{
    private PaymentTransaction $paymentTransaction;
    private bool $isWaitingTimeExceeded;
    public function __construct(PaymentTransaction $paymentTransaction, bool $isWaitingTimeExceeded)
    {
        $this->paymentTransaction = $paymentTransaction;
        $this->isWaitingTimeExceeded = $isWaitingTimeExceeded;
    }
    public function isWaiting() : bool
    {
        return $this->getStatusCode()->isPending() && !$this->isWaitingTimeExceeded;
    }
    public function getStatusCode() : StatusCode
    {
        return $this->paymentTransaction->getStatusCode();
    }
    public function getPaymentTransaction() : PaymentTransaction
    {
        return $this->paymentTransaction;
    }
}
