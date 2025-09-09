<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\MemoryCachingCartProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\RoundingTotalsCartProvider;
/**
 * Class PaymentLinkRequest
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks
 * @internal
 */
class PaymentLinkRequest
{
    private CartProvider $cartProvider;
    private string $returnUrl;
    private ?\DateTime $expiresAt;
    /**
     * @param CartProvider $cartProvider
     * @param string $returnUrl
     */
    public function __construct(CartProvider $cartProvider, string $returnUrl, ?\DateTime $expiresAt = null)
    {
        $this->cartProvider = new MemoryCachingCartProvider(new RoundingTotalsCartProvider($cartProvider));
        $this->returnUrl = $returnUrl;
        $this->expiresAt = $expiresAt;
    }
    public function getCartProvider() : CartProvider
    {
        return $this->cartProvider;
    }
    public function getReturnUrl() : string
    {
        return $this->returnUrl;
    }
    public function getExpiresAt() : ?\DateTime
    {
        return $this->expiresAt;
    }
}
