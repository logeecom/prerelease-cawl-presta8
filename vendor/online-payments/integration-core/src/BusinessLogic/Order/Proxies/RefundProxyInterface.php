<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Order\Proxies;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Refund\RefundRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Refund\RefundResponse;
/**
 * Interface RefundProxyInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\Order\Proxies
 */
interface RefundProxyInterface
{
    public function create(RefundRequest $refundRequest) : RefundResponse;
}
