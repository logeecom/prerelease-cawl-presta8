<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Exceptions\InvalidApiResponseException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentCapture;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslatableLabel;
use CAWL\OnlinePayments\Sdk\Domain\Capture;
/**
 * Class PaymentCaptureResponseTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 * @internal
 */
class PaymentCaptureResponseTransformer
{
    public static function transform(Capture $capture) : PaymentCapture
    {
        if (null === $capture->getCaptureOutput() || null === $capture->getStatusOutput() || null === $capture->getCaptureOutput()->getOperationReferences() || null === $capture->getCaptureOutput()->getOperationReferences()->getMerchantReference()) {
            throw new InvalidApiResponseException(new TranslatableLabel('Refund response is invalid. Refund status details missing in API response.', 'paymentProcessor.proxy.InvalidApiResponse'));
        }
        return new PaymentCapture(StatusCode::parse((int) $capture->getStatusOutput()->getStatusCode()), Amount::fromInt($capture->getCaptureOutput()->getAmountOfMoney()->getAmount(), Currency::fromIsoCode($capture->getCaptureOutput()->getAmountOfMoney()->getCurrencyCode())));
    }
}
