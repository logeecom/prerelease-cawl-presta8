<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
/**
 * Class PaymentRefund.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Payment
 * @internal
 */
class PaymentRefund
{
    private StatusCode $statusCode;
    private Amount $amount;
    public function __construct(StatusCode $statusCode, Amount $amount)
    {
        $this->statusCode = $statusCode;
        $this->amount = $amount;
    }
    public function getStatusCode() : StatusCode
    {
        return $this->statusCode;
    }
    public function getAmount() : Amount
    {
        return $this->amount;
    }
}
