<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks;

use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\HostedTokenization;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\HostedTokenizationProxyInterface;

/**
 * Class MockHostedTokenizationProxy.
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks
 */
class MockHostedTokenizationProxy implements HostedTokenizationProxyInterface
{
    private HostedTokenization $hostedTokenization;

    public function __construct(HostedTokenization $hostedTokenization)
    {
        $this->hostedTokenization = $hostedTokenization;
    }

    /**
     * @param Cart $cart
     * @param Token[] $savedTokens
     * @return HostedTokenization
     */
    public function create(Cart $cart, array $savedTokens = []): HostedTokenization
    {
        return $this->hostedTokenization;
    }

    public function getToken(string $customerId, string $tokenId): ?Token
    {
        return null;
    }

    public function deleteToken(string $tokenId): void
    {
    }
}