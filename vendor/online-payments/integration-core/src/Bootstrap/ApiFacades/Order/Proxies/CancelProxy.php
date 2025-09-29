<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies;

use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers\CreateCancelRequestTransformer;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\Transformers\CreateCancelResponseTransformer;
use CAWL\OnlinePayments\Core\Bootstrap\Sdk\MerchantClientFactory;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Cancel\CancelResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\ContextLogProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Order\Proxies\CancelProxyInterface;
/**
 * CancelProxy.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies
 */
class CancelProxy implements CancelProxyInterface
{
    private MerchantClientFactory $clientFactory;
    public function __construct(MerchantClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }
    public function create(CancelRequest $cancelRequest) : CancelResponse
    {
        ContextLogProvider::getInstance()->setPaymentNumber($cancelRequest->getPaymentId()->getTransactionId());
        return CreateCancelResponseTransformer::transform($this->clientFactory->get()->payments()->cancelPayment((string) $cancelRequest->getPaymentId(), CreateCancelRequestTransformer::transform($cancelRequest)));
    }
}
