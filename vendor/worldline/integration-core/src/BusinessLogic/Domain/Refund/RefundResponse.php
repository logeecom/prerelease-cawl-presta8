<?php

namespace OnlinePayments\Core\BusinessLogic\Domain\Refund;

use OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;

/**
 * Class RefundResponse.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Refund
 */
class RefundResponse
{
    private StatusCode $statusCode;

    private string $status;

    /**
     * @param StatusCode $statusCode
     * @param string $status
     */
    public function __construct(StatusCode $statusCode, string $status)
    {
        $this->statusCode = $statusCode;
        $this->status = $status;
    }

    public function getStatusCode(): StatusCode
    {
        return $this->statusCode;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}