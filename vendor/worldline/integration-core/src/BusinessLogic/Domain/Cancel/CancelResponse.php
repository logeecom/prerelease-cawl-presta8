<?php

namespace OnlinePayments\Core\BusinessLogic\Domain\Cancel;

use OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;

/**
 * Class CancelResponse.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Cancel
 */
class CancelResponse
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