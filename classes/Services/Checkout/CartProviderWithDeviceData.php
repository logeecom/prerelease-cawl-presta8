<?php

namespace CAWL\OnlinePayments\Classes\Services\Checkout;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Customer\Device;
/**
 * Class CartProviderWithDeviceData.
 *
 * @package OnlinePayments\Classes\Services\Checkout
 * @internal
 */
class CartProviderWithDeviceData implements CartProvider
{
    private CartProvider $cartProviderService;
    private ?Device $deviceData;
    public function __construct(CartProvider $cartProviderService, ?Device $deviceData = null)
    {
        $this->cartProviderService = $cartProviderService;
        $this->deviceData = $deviceData;
    }
    public function get() : Cart
    {
        $cart = $this->cartProviderService->get();
        if (null !== $this->deviceData) {
            $cart->getCustomer()->setDevice($this->deviceData);
        }
        return $cart;
    }
}
