<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Order\Proxies;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelResponse;
/**
 * Interface CancelProxyInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\Order\Proxies
 * @internal
 */
interface CancelProxyInterface
{
    public function create(CancelRequest $cancelRequest) : CancelResponse;
}
