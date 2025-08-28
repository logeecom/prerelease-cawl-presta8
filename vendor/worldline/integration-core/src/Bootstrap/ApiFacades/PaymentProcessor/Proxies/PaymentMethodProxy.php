<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies;

use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\GetPaymentProductsParamsTransformer;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\GetPaymentProductsResponseTransformer;
use OnlinePayments\Core\Bootstrap\Sdk\MerchantClientFactory;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\ContextLogProvider;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\PaymentMethodProxyInterface;

/**
 * Class PaymentMethodProxy.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies
 */
class PaymentMethodProxy implements PaymentMethodProxyInterface
{
    private MerchantClientFactory $clientFactory;

    public function __construct(MerchantClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    public function getAvailablePaymentMethods(Cart $cart): PaymentMethodCollection
    {
        ContextLogProvider::getInstance()->setCurrentOrder($cart->getMerchantReference());

        return GetPaymentProductsResponseTransformer::transform(
            $this->clientFactory->get()->products()->getPaymentProducts(
                GetPaymentProductsParamsTransformer::transform($cart)
            )
        );
    }
}