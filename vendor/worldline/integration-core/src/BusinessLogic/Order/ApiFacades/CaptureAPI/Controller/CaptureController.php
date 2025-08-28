<?php

namespace OnlinePayments\Core\BusinessLogic\Order\ApiFacades\CaptureAPI\Controller;

use OnlinePayments\Core\BusinessLogic\Order\ApiFacades\CaptureAPI\Response\CreateCaptureResponse;
use OnlinePayments\Core\BusinessLogic\Domain\Capture\CaptureRequest;
use OnlinePayments\Core\BusinessLogic\Order\Services\Capture\CaptureService;

/**
 * Class CaptureController
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\CaptureAPI\Controller
 */
class CaptureController
{
    private CaptureService $captureService;

    /**
     * @param CaptureService $captureService
     */
    public function __construct(CaptureService $captureService)
    {
        $this->captureService = $captureService;
    }

    public function handle(CaptureRequest $request): CreateCaptureResponse
    {
        return new CreateCaptureResponse($this->captureService->handle($request));
    }
}