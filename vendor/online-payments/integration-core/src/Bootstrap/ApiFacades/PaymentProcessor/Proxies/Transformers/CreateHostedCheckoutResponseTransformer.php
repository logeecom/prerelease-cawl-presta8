<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;
use CAWL\OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;
/**
 * Class CreateHostedCheckoutResponseTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CreateHostedCheckoutResponseTransformer
{
    public static function transform(CreateHostedCheckoutResponse $createHostedCheckout) : PaymentResponse
    {
        return new PaymentResponse(new PaymentTransaction((string) $createHostedCheckout->getMerchantReference(), PaymentId::parse((string) $createHostedCheckout->getHostedCheckoutId()), (string) $createHostedCheckout->getRETURNMAC()), $createHostedCheckout->getRedirectUrl());
    }
}
