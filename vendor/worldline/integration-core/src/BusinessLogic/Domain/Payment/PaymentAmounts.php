<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
/**
 * Class PaymentAmounts.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Payment
 * @internal
 */
class PaymentAmounts
{
    private ?Amount $refundedAmount;
    private ?Amount $refundRequestedAmount;
    private ?Amount $capturedAmount;
    private ?Amount $captureRequestedAmount;
    private ?Amount $cancelledAmount;
    /**
     * @param Amount|null $refundedAmount
     * @param Amount|null $refundRequestedAmount
     * @param Amount|null $capturedAmount
     * @param Amount|null $captureRequestedAmount
     * @param Amount|null $cancelledAmount
     */
    public function __construct(?Amount $refundedAmount, ?Amount $refundRequestedAmount, ?Amount $capturedAmount, ?Amount $captureRequestedAmount, ?Amount $cancelledAmount)
    {
        $this->refundedAmount = $refundedAmount;
        $this->refundRequestedAmount = $refundRequestedAmount;
        $this->capturedAmount = $capturedAmount;
        $this->captureRequestedAmount = $captureRequestedAmount;
        $this->cancelledAmount = $cancelledAmount;
    }
    public function getRefundedAmount() : ?Amount
    {
        return $this->refundedAmount;
    }
    public function getRefundRequestedAmount() : ?Amount
    {
        return $this->refundRequestedAmount;
    }
    public function getCapturedAmount() : ?Amount
    {
        return $this->capturedAmount;
    }
    public function getCaptureRequestedAmount() : ?Amount
    {
        return $this->captureRequestedAmount;
    }
    public function getCancelledAmount() : ?Amount
    {
        return $this->cancelledAmount;
    }
}
