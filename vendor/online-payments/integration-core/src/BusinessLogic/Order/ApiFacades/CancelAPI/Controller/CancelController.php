<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Order\ApiFacades\CancelAPI\Controller;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Order\ApiFacades\CancelAPI\Response\CreateCancelResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Order\Services\Cancel\CancelService;
/**
 * Class CancelController
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\CancelAPI\Controller
 */
class CancelController
{
    private CancelService $cancelService;
    /**
     * @param CancelService $cancelService
     */
    public function __construct(CancelService $cancelService)
    {
        $this->cancelService = $cancelService;
    }
    public function handle(CancelRequest $request) : CreateCancelResponse
    {
        return new CreateCancelResponse($this->cancelService->handle($request));
    }
}
