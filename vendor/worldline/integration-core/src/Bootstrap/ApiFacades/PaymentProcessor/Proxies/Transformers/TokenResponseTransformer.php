<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use CAWL\OnlinePayments\Sdk\Domain\TokenResponse;
/**
 * Class TokenResponseTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 * @internal
 */
class TokenResponseTransformer
{
    public static function transform(string $customerId, TokenResponse $tokenResponse) : ?Token
    {
        if ($tokenResponse->getId() && \false === $tokenResponse->getIsTemporary() && $tokenResponse->getCard() && $tokenResponse->getCard()->getData() && $tokenResponse->getCard()->getData()->getCardWithoutCvv()) {
            return new Token($customerId, (string) $tokenResponse->getId(), (string) $tokenResponse->getPaymentProductId(), (string) $tokenResponse->getCard()->getData()->getCardWithoutCvv()->getCardNumber(), (string) $tokenResponse->getCard()->getData()->getCardWithoutCvv()->getExpiryDate());
        }
        return null;
    }
}
