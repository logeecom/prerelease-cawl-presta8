<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
/**
 * Interface PaymentMethodProxy.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies
 * @internal
 */
interface PaymentMethodProxyInterface
{
    /**
     * @param Cart $cart
     * @return PaymentMethodCollection
     */
    public function getAvailablePaymentMethods(Cart $cart) : PaymentMethodCollection;
}
