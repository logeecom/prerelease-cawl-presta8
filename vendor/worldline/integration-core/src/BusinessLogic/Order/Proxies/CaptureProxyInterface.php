<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Order\Proxies;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Capture\CaptureRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Capture\CaptureResponse;
/**
 * Interface CaptureProxyInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\Order\Proxies
 * @internal
 */
interface CaptureProxyInterface
{
    public function create(CaptureRequest $captureRequest) : CaptureResponse;
}
