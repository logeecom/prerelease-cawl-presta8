<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart;

/**
 * Class CartProvider
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart
 * @internal
 */
interface CartProvider
{
    public function get() : Cart;
}
