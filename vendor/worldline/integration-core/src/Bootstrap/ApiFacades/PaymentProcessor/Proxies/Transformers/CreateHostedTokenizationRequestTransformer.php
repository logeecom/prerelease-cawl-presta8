<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use OnlinePayments\Sdk\Domain\CreateHostedTokenizationRequest;

/**
 * Class CreateHostedTokenizationRequestTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CreateHostedTokenizationRequestTransformer
{

    public static function transform(Cart $cart, array $savedTokens = []): CreateHostedTokenizationRequest
    {
        $request = new CreateHostedTokenizationRequest();
        $request->setAskConsumerConsent(!$cart->getCustomer()->isGuest());
        $request->setLocale($cart->getCustomer()->getLocale());

        if (!empty($savedTokens)) {
            $request->setTokens(join(',', array_map(function (Token $token) {
                return $token->getTokenId();
            }, $savedTokens)));
        }

        return $request;
    }
}