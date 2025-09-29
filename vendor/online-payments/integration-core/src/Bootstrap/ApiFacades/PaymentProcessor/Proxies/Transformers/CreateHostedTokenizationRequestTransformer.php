<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use CAWL\OnlinePayments\Sdk\Domain\CreateHostedTokenizationRequest;
use CAWL\OnlinePayments\Sdk\Domain\PaymentProductFilterHostedTokenization;
use CAWL\OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedTokenization;
/**
 * Class CreateHostedTokenizationRequestTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CreateHostedTokenizationRequestTransformer
{
    public static function transform(Cart $cart, array $savedTokens = [], ?PaymentProductId $productId = null, string $template = '') : CreateHostedTokenizationRequest
    {
        $request = new CreateHostedTokenizationRequest();
        $request->setAskConsumerConsent(!$cart->getCustomer()->isGuest());
        $request->setLocale($cart->getCustomer()->getLocale());
        if ($productId && $productId->isCardType()) {
            $productFilter = new PaymentProductFiltersHostedTokenization();
            $filterRestriction = new PaymentProductFilterHostedTokenization();
            $filterRestriction->setProducts([(int) $productId->getId()]);
            $productFilter->setRestrictTo($filterRestriction);
            $request->setPaymentProductFilters($productFilter);
        }
        if (!empty($template)) {
            $request->setVariant($template);
        }
        if (!empty($savedTokens)) {
            $request->setTokens(\join(',', \array_map(function (Token $token) {
                return $token->getTokenId();
            }, $savedTokens)));
        }
        return $request;
    }
}
