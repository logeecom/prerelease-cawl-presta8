<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentRequest;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use OnlinePayments\Sdk\Domain\CreatePaymentRequest;
use OnlinePayments\Sdk\Domain\SurchargeSpecificInput;

/**
 * Class CreatePaymentRequestTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CreatePaymentRequestTransformer
{
    public static function transform(
        PaymentRequest $input, CardsSettings $cardsSettings, PaymentSettings $paymentSettings, ?Token $token = null
    ): CreatePaymentRequest {
        $cart = $input->getCartProvider()->get();

        $request = new CreatePaymentRequest();
        if (null == $token) {
            $request->setHostedTokenizationId($input->getHostedTokenizationId());
        }

        $order = OrderTransformer::transform($cart);

        if ($paymentSettings->isApplySurcharge()) {
            $surchargeSpecificInput = new SurchargeSpecificInput();
            $surchargeSpecificInput->setMode('on-behalf-of');
            $order->setSurchargeSpecificInput($surchargeSpecificInput);
        }

        $request->setOrder($order);
        $request->setCardPaymentMethodSpecificInput(CardPaymentMethodSpecificInputTransformer::transform(
            $cart,
            $input->getReturnUrl(),
            $cardsSettings,
            $paymentSettings,
            null,
            $token
        ));

        return $request;
    }


}