<?php

namespace OnlinePayments\Core\BusinessLogic\Order\Services\Capture;

use OnlinePayments\Core\BusinessLogic\Domain\Capture\CaptureRequest;
use OnlinePayments\Core\BusinessLogic\Domain\Capture\CaptureResponse;
use OnlinePayments\Core\BusinessLogic\Order\Proxies\CaptureProxyInterface;

/**
 * Class CaptureService
 *
 * @package OnlinePayments\Core\BusinessLogic\Order\Services\Capture
 */
class CaptureService
{
    private CaptureProxyInterface $captureProxy;

    public function __construct(CaptureProxyInterface $captureProxy)
    {
        $this->captureProxy = $captureProxy;
    }

    public function handle(CaptureRequest $request): CaptureResponse
    {
        return $this->captureProxy->create($request);
    }
}