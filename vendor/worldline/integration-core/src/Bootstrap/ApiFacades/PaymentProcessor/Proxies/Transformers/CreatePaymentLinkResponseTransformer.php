<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLink;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkResponse;
use OnlinePayments\Sdk\Domain\PaymentLinkResponse as SdkPaymentLinkResponse;

/**
 * Class CreatePaymentLinkResponseTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CreatePaymentLinkResponseTransformer
{
    public static function transform(SdkPaymentLinkResponse $linkResponse): PaymentLinkResponse
    {
        return new PaymentLinkResponse(
            new PaymentLink(
                $linkResponse->getPaymentLinkId(),
                $linkResponse->getPaymentLinkOrder()->getMerchantReference(),
                $linkResponse->getPaymentId(),
                $linkResponse->getExpirationDate(),
                $linkResponse->getRedirectionUrl(),
                $linkResponse->getStatus()
            )
        );
    }
}