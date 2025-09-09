<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart;

/**
 * Class MemoryCachingCartProvider.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart
 * @internal
 */
class MemoryCachingCartProvider implements CartProvider
{
    private ?Cart $cart = null;
    private CartProvider $cartProvider;
    public function __construct(CartProvider $cartProvider)
    {
        $this->cartProvider = $cartProvider;
    }
    public function get() : Cart
    {
        if (null === $this->cart) {
            $this->cart = $this->cartProvider->get();
        }
        return $this->cart;
    }
}
