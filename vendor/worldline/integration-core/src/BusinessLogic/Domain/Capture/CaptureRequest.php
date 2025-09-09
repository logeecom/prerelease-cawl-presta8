<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Capture;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
/**
 * Class CaptureRequest.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Capture
 * @internal
 */
class CaptureRequest
{
    private PaymentId $paymentId;
    private ?Amount $amount;
    private ?string $merchantReference;
    /**
     * @param PaymentId $paymentId
     * @param Amount|null $amount
     * @param string|null $merchantReference
     */
    public function __construct(PaymentId $paymentId, ?Amount $amount = null, ?string $merchantReference = null)
    {
        $this->paymentId = $paymentId;
        $this->amount = $amount;
        $this->merchantReference = $merchantReference;
    }
    public function getPaymentId() : PaymentId
    {
        return $this->paymentId;
    }
    public function getAmount() : ?Amount
    {
        return $this->amount;
    }
    public function getMerchantReference() : ?string
    {
        return $this->merchantReference;
    }
}
