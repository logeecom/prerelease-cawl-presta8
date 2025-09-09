<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Order\Services\Refund;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Refund\RefundRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Refund\RefundResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Order\Proxies\RefundProxyInterface;
/**
 * Class RefundService
 *
 * @package OnlinePayments\Core\BusinessLogic\Order\Services\Refund
 */
class RefundService
{
    private RefundProxyInterface $refundProxy;
    public function __construct(RefundProxyInterface $refundProxy)
    {
        $this->refundProxy = $refundProxy;
    }
    public function handle(RefundRequest $request) : RefundResponse
    {
        return $this->refundProxy->create($request);
    }
}
