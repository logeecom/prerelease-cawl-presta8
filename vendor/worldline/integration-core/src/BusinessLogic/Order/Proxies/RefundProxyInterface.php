<?php

namespace OnlinePayments\Core\BusinessLogic\Order\Proxies;

use OnlinePayments\Core\BusinessLogic\Domain\Refund\RefundRequest;
use OnlinePayments\Core\BusinessLogic\Domain\Refund\RefundResponse;

/**
 * Interface RefundProxyInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\Order\Proxies
 */
interface RefundProxyInterface
{
    public function create(RefundRequest $refundRequest): RefundResponse;
}