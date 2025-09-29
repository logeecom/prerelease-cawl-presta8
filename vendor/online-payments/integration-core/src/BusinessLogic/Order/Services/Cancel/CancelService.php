<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Order\Services\Cancel;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Order\Proxies\CancelProxyInterface;
/**
 * Class CancelService
 *
 * @package OnlinePayments\Core\BusinessLogic\Order\Services\Cancel
 */
class CancelService
{
    private CancelProxyInterface $cancelProxy;
    public function __construct(CancelProxyInterface $cancelProxy)
    {
        $this->cancelProxy = $cancelProxy;
    }
    public function handle(CancelRequest $request) : CancelResponse
    {
        return $this->cancelProxy->create($request);
    }
}
