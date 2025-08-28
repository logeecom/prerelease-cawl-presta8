<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\HostedCheckout\HostedCheckoutSessionRequest;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\GPayThreeDSecure;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\MobilePaymentProduct320SpecificInput;
use OnlinePayments\Sdk\Domain\PaymentProductFilter;
use OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckout;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\RedirectPaymentProduct5402SpecificInput;
use OnlinePayments\Sdk\Domain\SurchargeSpecificInput;

/**
 * Class CreateHostedCheckoutRequestTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CreateHostedCheckoutRequestTransformer
{
    public static function transform(
        HostedCheckoutSessionRequest $input,
        CardsSettings $cardsSettings,
        PaymentSettings $paymentSettings,
        ?Token $token = null
    ): CreateHostedCheckoutRequest {
        $cart = $input->getCartProvider()->get();

        $request = new CreateHostedCheckoutRequest();

        $hostedCheckoutSpecificInput = new HostedCheckoutSpecificInput();
        $hostedCheckoutSpecificInput->setReturnUrl($input->getReturnUrl());

        $filters = new PaymentProductFiltersHostedCheckout();
        $productFilter = new PaymentProductFilter();
        $productFilter->setProducts(array_map('intval', PaymentProductId::getForHostedCheckoutPage()));
        if (null !== $input->getPaymentProductId()) {
            $productFilter->setProducts([(int)$input->getPaymentProductId()->getId()]);
        }

        $filters->setRestrictTo($productFilter);
        $hostedCheckoutSpecificInput->setPaymentProductFilters($filters);

        $order = OrderTransformer::transform($cart);

        if ($paymentSettings->isApplySurcharge()) {
            $surchargeSpecificInput = new SurchargeSpecificInput();
            $surchargeSpecificInput->setMode('on-behalf-of');
            $order->setSurchargeSpecificInput($surchargeSpecificInput);
        }

        $request->setOrder($order);
        $request->setHostedCheckoutSpecificInput($hostedCheckoutSpecificInput);
        $cardPaymentMethodSpecificInput = CardPaymentMethodSpecificInputTransformer::transform(
            $cart,
            $input->getReturnUrl(),
            $cardsSettings,
            $paymentSettings,
            $input->getPaymentProductId(),
            $token
        );
        $request->setCardPaymentMethodSpecificInput($cardPaymentMethodSpecificInput);

        if (null !== $input->getPaymentProductId() && $input->getPaymentProductId()->isMobileType()) {
            $mobilePaymentMethodSpecificInput = new MobilePaymentMethodSpecificInput();
            $mobilePaymentMethodSpecificInput->setPaymentProductId($input->getPaymentProductId()->getId());

            if (PaymentProductId::googlePay()->equals($input->getPaymentProductId())) {
                $mobilePaymentProduct320SpecificInput = new MobilePaymentProduct320SpecificInput();
                $gPayThreeDSecure = new GPayThreeDSecure();

                $threeDSecure = $cardPaymentMethodSpecificInput->getThreeDSecure();
                $gPayThreeDSecure->setSkipAuthentication($threeDSecure->getSkipAuthentication());
                $gPayThreeDSecure->setChallengeIndicator($threeDSecure->getchallengeIndicator());
                $gPayThreeDSecure->setRedirectionData($threeDSecure->getRedirectionData());
                $gPayThreeDSecure->setExemptionRequest($threeDSecure->getexemptionRequest());

                $mobilePaymentProduct320SpecificInput->setThreeDSecure($gPayThreeDSecure);
                $mobilePaymentMethodSpecificInput->setPaymentProduct320SpecificInput($mobilePaymentProduct320SpecificInput);
            }

            $request->setMobilePaymentMethodSpecificInput($mobilePaymentMethodSpecificInput);
        }

        $redirectPaymentMethodSpecificInput = new RedirectPaymentMethodSpecificInput();
        $redirectPaymentProduct5402SpecificInput = new RedirectPaymentProduct5402SpecificInput();
        $redirectPaymentProduct5402SpecificInput->setCompleteRemainingPaymentAmount(true);

        if (null !== $input->getPaymentProductId() &&
            $input->getPaymentProductId()->equals(PaymentProductId::illicado()->getId())) {
            $redirectPaymentMethodSpecificInput->setRequiresApproval(false);
        }

        if ($input->getPaymentProductId() !== null && $input->getPaymentProductId()->isRedirectType()) {
            $redirectPaymentMethodSpecificInput->setPaymentProductId((int)$input->getPaymentProductId()->getId());
        }

        $redirectPaymentMethodSpecificInput->setPaymentProduct5402SpecificInput($redirectPaymentProduct5402SpecificInput);

        $request->setRedirectPaymentMethodSpecificInput($redirectPaymentMethodSpecificInput);

        return $request;
    }
}