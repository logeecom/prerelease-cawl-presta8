<?php

namespace OnlinePayments\Core\BusinessLogic\Order\Services\Cancel;

use OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelRequest;
use OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelResponse;
use OnlinePayments\Core\BusinessLogic\Order\Proxies\CancelProxyInterface;

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

    public function handle(CancelRequest $request): CancelResponse
    {
        return $this->cancelProxy->create($request);
    }
}