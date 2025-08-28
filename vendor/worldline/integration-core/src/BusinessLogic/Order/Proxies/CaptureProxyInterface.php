<?php

namespace OnlinePayments\Core\BusinessLogic\Order\Proxies;

use OnlinePayments\Core\BusinessLogic\Domain\Capture\CaptureRequest;
use OnlinePayments\Core\BusinessLogic\Domain\Capture\CaptureResponse;

/**
 * Interface CaptureProxyInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\Order\Proxies
 */
interface CaptureProxyInterface
{
    public function create(CaptureRequest $captureRequest): CaptureResponse;
}