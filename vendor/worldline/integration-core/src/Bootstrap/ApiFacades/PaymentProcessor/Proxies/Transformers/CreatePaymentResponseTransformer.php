<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Exceptions\InvalidApiResponseException;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentResponse;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslatableLabel;
use OnlinePayments\Sdk\Domain\CreatePaymentResponse;

/**
 * Class CreatePaymentResponseTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CreatePaymentResponseTransformer
{

    public static function transform(CreatePaymentResponse $createPayment): PaymentResponse
    {
        if (
            null === $createPayment->getPayment() ||
            null === $createPayment->getPayment()->getPaymentOutput() ||
            null === $createPayment->getPayment()->getPaymentOutput()->getReferences() ||
            null === $createPayment->getPayment()->getPaymentOutput()->getReferences()->getMerchantReference()
        ) {
            throw new InvalidApiResponseException(new TranslatableLabel(
                'Payment response is invalid. Payment details missing in API response.',
                'paymentProcessor.proxy.InvalidApiResponse'
            ));
        }

        $returnHmac = null;
        $returnUrl = null;
        if ($createPayment->getMerchantAction() && $createPayment->getMerchantAction()->getRedirectData()) {
            $returnHmac = $createPayment->getMerchantAction()->getRedirectData()->getRETURNMAC();
            $returnUrl = $createPayment->getMerchantAction()->getRedirectData()->getRedirectURL();
        }

        return new PaymentResponse(
            new PaymentTransaction(
                $createPayment->getPayment()->getPaymentOutput()->getReferences()->getMerchantReference(),
                PaymentId::parse((string)$createPayment->getPayment()->getId()),
                $returnHmac,
                StatusCode::parse((int)$createPayment->getPayment()->getStatusOutput()->getStatusCode()),
                null,
                null,
                null,
                null,
                $createPayment->getPayment()->getPaymentOutput()->paymentMethod
            ),
            $returnUrl
        );
    }
}