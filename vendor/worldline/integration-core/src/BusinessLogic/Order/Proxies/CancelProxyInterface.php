<?php

namespace OnlinePayments\Core\BusinessLogic\Order\Proxies;

use OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelRequest;
use OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelResponse;

/**
 * Interface CancelProxyInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\Order\Proxies
 */
interface CancelProxyInterface
{
    public function create(CancelRequest $cancelRequest): CancelResponse;
}