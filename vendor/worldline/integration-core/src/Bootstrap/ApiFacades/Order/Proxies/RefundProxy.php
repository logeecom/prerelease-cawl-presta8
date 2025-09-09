<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies;

use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers\CreateRefundRequestTransformer;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers\CreateRefundResponseTransformer;
use CAWL\OnlinePayments\Core\Bootstrap\Sdk\MerchantClientFactory;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\ContextLogProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Refund\RefundRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Refund\RefundResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Order\Proxies\RefundProxyInterface;
/**
 * RefundProxy.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies
 * @internal
 */
class RefundProxy implements RefundProxyInterface
{
    private MerchantClientFactory $clientFactory;
    public function __construct(MerchantClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }
    public function create(RefundRequest $refundRequest) : RefundResponse
    {
        ContextLogProvider::getInstance()->setPaymentNumber($refundRequest->getPaymentId()->getTransactionId());
        return CreateRefundResponseTransformer::transform($this->clientFactory->get()->payments()->refundPayment((string) $refundRequest->getPaymentId(), CreateRefundRequestTransformer::transform($refundRequest)));
    }
}
