<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLink;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkResponse;
use CAWL\OnlinePayments\Sdk\Domain\PaymentLinkResponse as SdkPaymentLinkResponse;
/**
 * Class CreatePaymentLinkResponseTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CreatePaymentLinkResponseTransformer
{
    public static function transform(SdkPaymentLinkResponse $linkResponse) : PaymentLinkResponse
    {
        return new PaymentLinkResponse(new PaymentLink($linkResponse->getPaymentLinkId(), $linkResponse->getPaymentLinkOrder()->getMerchantReference(), $linkResponse->getPaymentId(), $linkResponse->getExpirationDate(), $linkResponse->getRedirectionUrl(), $linkResponse->getStatus()));
    }
}
