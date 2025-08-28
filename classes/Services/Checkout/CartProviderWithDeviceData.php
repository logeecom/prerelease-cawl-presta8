<?php

namespace OnlinePayments\Classes\Services\Checkout;

use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Customer\Device;

/**
 * Class CartProviderWithDeviceData.
 *
 * @package OnlinePayments\Classes\Services\Checkout
 */
class CartProviderWithDeviceData implements  CartProvider
{
    private CartProvider $cartProviderService;
    private ?Device $deviceData;

    public function __construct(CartProvider $cartProviderService,  ?Device $deviceData = null)
    {
        $this->cartProviderService = $cartProviderService;
        $this->deviceData = $deviceData;
    }

    public function get(): Cart
    {
        $cart = $this->cartProviderService->get();

        if (null !== $this->deviceData) {
            $cart->getCustomer()->setDevice($this->deviceData);
        }

        return $cart;
    }
}