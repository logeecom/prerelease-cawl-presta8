<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\Cart;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAction;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use CAWL\OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use CAWL\OnlinePayments\Sdk\Domain\PaymentProduct130SpecificInput;
use CAWL\OnlinePayments\Sdk\Domain\PaymentProduct130SpecificThreeDSecure;
use CAWL\OnlinePayments\Sdk\Domain\RedirectionData;
use CAWL\OnlinePayments\Sdk\Domain\ThreeDSecure;
/**
 * Class CardPaymentMethodSpecificInputTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CardPaymentMethodSpecificInputTransformer
{
    public static function transform(Cart $cart, string $getReturnUrl, CardsSettings $cardsSettings, PaymentSettings $paymentSettings, ?PaymentMethodCollection $paymentMethodCollection = null, ?PaymentProductId $paymentProductId = null, ?Token $token = null) : CardPaymentMethodSpecificInput
    {
        $cardPaymentMethodSpecificInput = new CardPaymentMethodSpecificInput();
        if (null !== $token) {
            $cardPaymentMethodSpecificInput->setToken($token->getTokenId());
        }
        $redirectionData = new RedirectionData();
        $redirectionData->setReturnUrl($getReturnUrl);
        $threeDSecure = new ThreeDSecure();
        $threeDSecure->setRedirectionData($redirectionData);
        $threeDSecure->setSkipAuthentication(!$cardsSettings->isEnable3ds());
        if (null !== $paymentProductId && PaymentProductId::maestro()->equals($paymentProductId)) {
            $threeDSecure->setSkipAuthentication(\false);
        }
        if ($cardsSettings->isEnable3ds() && $cardsSettings->isEnable3dsExemption() && null !== $cart->getTotalInEUR() && $cardsSettings->getExemptionLimit()->getValue() >= $cart->getTotalInEUR()->getValue() && null !== $cardsSettings->getExemptionType()) {
            $threeDSecure->setExemptionRequest($cardsSettings->getExemptionType()->getType());
            $threeDSecure->setSkipAuthentication(\true);
            $threeDSecure->setSkipSoftDecline(\false);
        }
        if ($cardsSettings->isEnforceStrongAuthentication()) {
            $threeDSecure->setChallengeIndicator('challenge-required');
        }
        if ($cardsSettings->isEnable3ds()) {
            $paymentProduct130SpecificInput = new PaymentProduct130SpecificInput();
            $paymentProduct130ThreeDSecure = new PaymentProduct130SpecificThreeDSecure();
            $paymentProduct130ThreeDSecure->setUsecase('single-amount');
            $paymentProduct130ThreeDSecure->setNumberOfItems(\min($cart->getLineItems()->getCount(), 99));
            $paymentProduct130ThreeDSecure->setAcquirerExemption($threeDSecure->getSkipAuthentication());
            $paymentProduct130SpecificInput->setThreeDSecure($paymentProduct130ThreeDSecure);
            $cardPaymentMethodSpecificInput->setPaymentProduct130SpecificInput($paymentProduct130SpecificInput);
        }
        $cardPaymentMethodSpecificInput->setThreeDSecure($threeDSecure);
        if ($paymentProductId !== null && $paymentProductId->equals(PaymentProductId::illicado()->getId())) {
            return $cardPaymentMethodSpecificInput;
        }
        $cardPaymentMethodSpecificInput->setAuthorizationMode($paymentSettings->getPaymentAction()->getType());
        if ($paymentProductId !== null && $paymentProductId->equals(PaymentProductId::mealvouchers()->getId())) {
            $cardPaymentMethodSpecificInput->setAuthorizationMode(PaymentAction::authorizeCapture()->getType());
        }
        if ($paymentProductId !== null && PaymentProductId::intersolve()->equals($paymentProductId->getId())) {
            $cardPaymentMethodSpecificInput->setAuthorizationMode(PaymentAction::authorizeCapture()->getType());
            if ($paymentMethodCollection && ($config = $paymentMethodCollection->get(PaymentProductId::intersolve()))) {
                $cardPaymentMethodSpecificInput->setPaymentProductId($config->getAdditionalData()->getProductId()->getId());
            }
        }
        if ($paymentProductId !== null && $paymentProductId->isCardType()) {
            $cardPaymentMethodSpecificInput->setPaymentProductId($paymentProductId->getId());
        }
        return $cardPaymentMethodSpecificInput;
    }
}
