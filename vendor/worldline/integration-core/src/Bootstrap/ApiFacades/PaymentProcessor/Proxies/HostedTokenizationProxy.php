<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies;

use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\CreateHostedTokenizationRequestTransformer;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\CreateHostedTokenizationResponseTransformer;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers\TokenResponseTransformer;
use OnlinePayments\Core\Bootstrap\Sdk\MerchantClientFactory;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidConnectionDetailsException;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\HostedTokenization;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\ContextLogProvider;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\HostedTokenizationProxyInterface;
use Throwable;

/**
 * Class HostedTokenizationProxy.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies
 */
class HostedTokenizationProxy implements HostedTokenizationProxyInterface
{
    private MerchantClientFactory $clientFactory;

    public function __construct(MerchantClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    public function create(Cart $cart, array $savedTokens = []): HostedTokenization
    {
        ContextLogProvider::getInstance()->setCurrentOrder($cart->getMerchantReference());

        return CreateHostedTokenizationResponseTransformer::transform(
            $this->clientFactory->get()->hostedTokenization()->createHostedTokenization(
                CreateHostedTokenizationRequestTransformer::transform($cart, $savedTokens)
            )
        );
    }

    public function getToken(string $customerId, string $tokenId): ?Token
    {
        try {
            return TokenResponseTransformer::transform($customerId, $this->clientFactory->get()->tokens()->getToken($tokenId));
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @param string $tokenId
     *
     * @return void
     *
     * @throws InvalidConnectionDetailsException
     */
    public function deleteToken(string $tokenId): void
    {
        $this->clientFactory->get()->tokens()->deleteToken($tokenId);
    }
}