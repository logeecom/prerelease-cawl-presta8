<?php

namespace OnlinePayments\Core\BusinessLogic\Domain\Capture;

use OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;

/**
 * Class CaptureResponse.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Capture
 */
class CaptureResponse
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