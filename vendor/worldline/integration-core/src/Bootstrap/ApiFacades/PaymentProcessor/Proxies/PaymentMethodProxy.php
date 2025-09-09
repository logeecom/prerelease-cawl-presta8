<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies;

use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\GetPaymentProductsParamsTransformer;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\GetPaymentProductsResponseTransformer;
use CAWL\OnlinePayments\Core\Bootstrap\Sdk\MerchantClientFactory;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\ContextLogProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\PaymentMethodProxyInterface;
/**
 * Class PaymentMethodProxy.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies
 * @internal
 */
class PaymentMethodProxy implements PaymentMethodProxyInterface
{
    private MerchantClientFactory $clientFactory;
    public function __construct(MerchantClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }
    public function getAvailablePaymentMethods(Cart $cart) : PaymentMethodCollection
    {
        ContextLogProvider::getInstance()->setCurrentOrder($cart->getMerchantReference());
        return GetPaymentProductsResponseTransformer::transform($this->clientFactory->get()->products()->getPaymentProducts(GetPaymentProductsParamsTransformer::transform($cart)));
    }
}
