<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies;

use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\CalculateSurchargeRequestTransformer;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\CalculateSurchargeResponseTransformer;
use OnlinePayments\Core\Bootstrap\Sdk\MerchantClientFactory;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\SurchargeRequest;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\SurchargeResponse;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\SurchargeProxyInterface;

/**
 * Class SurchargeProxy
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies
 */
class SurchargeProxy implements SurchargeProxyInterface
{
    private MerchantClientFactory $clientFactory;

    public function __construct(MerchantClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * @inheritDoc
     */
    public function calculateSurcharge(SurchargeRequest $request): ?SurchargeResponse
    {
        return CalculateSurchargeResponseTransformer::transform(
            $this->clientFactory->get()->services()->surchargeCalculation(
                CalculateSurchargeRequestTransformer::transform($request)
            )
        );
    }
}