<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks;

use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\PaymentMethodProxyInterface;

/**
 * Class MockPaymentMethodProxy.
 *
 * @package CheckoutAPI\Mocks
 */
class MockPaymentMethodProxy implements PaymentMethodProxyInterface
{
    private PaymentMethodCollection $availablePaymentMethods;

    public function __construct(PaymentMethodCollection $availablePaymentMethods)
    {
        $this->availablePaymentMethods = $availablePaymentMethods;
    }

    public function getAvailablePaymentMethods(Cart $cart): PaymentMethodCollection
    {
        return $this->availablePaymentMethods;
    }
}