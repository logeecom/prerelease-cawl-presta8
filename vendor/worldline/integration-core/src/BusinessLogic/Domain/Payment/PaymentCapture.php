<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
/**
 * Class PaymentCapture.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Payment
 */
class PaymentCapture
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
