<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedCheckout;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\MemoryCachingCartProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\RoundingTotalsCartProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
/**
 * Class HostedCheckoutSessionRequest.
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\HostedCheckout
 * @internal
 */
class HostedCheckoutSessionRequest
{
    private ?PaymentProductId $paymentProductId;
    private CartProvider $cartProvider;
    private string $returnUrl;
    private ?string $tokenId;
    public function __construct(CartProvider $cart, string $returnUrl, ?PaymentProductId $paymentProductId = null, ?string $tokenId = null)
    {
        $this->cartProvider = new MemoryCachingCartProvider(new RoundingTotalsCartProvider($cart));
        $this->returnUrl = $returnUrl;
        $this->paymentProductId = $paymentProductId;
        $this->tokenId = $tokenId;
    }
    public function getPaymentProductId() : ?PaymentProductId
    {
        return $this->paymentProductId;
    }
    public function getCartProvider() : CartProvider
    {
        return $this->cartProvider;
    }
    public function getReturnUrl() : string
    {
        return $this->returnUrl;
    }
    public function getTokenId() : ?string
    {
        return $this->tokenId;
    }
}
