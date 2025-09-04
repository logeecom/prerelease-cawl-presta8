<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use DateInterval;
use DateTime;
use DateTimeZone;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PayByLinkExpirationTime;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PayByLinkSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkRequest;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Sdk\Domain\CreatePaymentLinkRequest;
use OnlinePayments\Sdk\Domain\GPayThreeDSecure;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\MobilePaymentProduct320SpecificInput;
use OnlinePayments\Sdk\Domain\PaymentLinkSpecificInput;
use OnlinePayments\Sdk\Domain\PaymentProductFilter;
use OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckout;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\RedirectPaymentProduct5402SpecificInput;

/**
 * Class CreatePaymentLinkRequestTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CreatePaymentLinkRequestTransformer
{
    public static function transform(
        PaymentLinkRequest $input,
        CardsSettings $cardsSettings,
        PaymentSettings $paymentSettings,
        PayByLinkSettings $payByLinkSettings,
        PaymentMethodCollection $paymentMethodCollection
    ): CreatePaymentLinkRequest
    {
        $cart = $input->getCartProvider()->get();

        $request = new CreatePaymentLinkRequest();

        $specificInput = new PaymentLinkSpecificInput();
        $specificInput->setExpirationDate(
            self::getExpirationDate($input->getExpiresAt(), $payByLinkSettings->getExpirationTime())
        );
        $request->setPaymentLinkSpecificInput($specificInput);
        $hostedCheckoutSpecificInput = new HostedCheckoutSpecificInput();
        $hostedCheckoutSpecificInput->setReturnUrl($input->getReturnUrl());

        $filters = new PaymentProductFiltersHostedCheckout();
        $productFilter = new PaymentProductFilter();
        $productFilter->setProducts([(int)PaymentProductId::mealvouchers()->getId()]);
        $filters->setExclude($productFilter);
        $hostedCheckoutSpecificInput->setPaymentProductFilters($filters);

        $request->setOrder(OrderTransformer::transform($cart));
        $request->setHostedCheckoutSpecificInput($hostedCheckoutSpecificInput);
        $cardPaymentMethodSpecificInput = CardPaymentMethodSpecificInputTransformer::transform(
            $cart,
            $input->getReturnUrl(),
            $cardsSettings,
            $paymentSettings,
            $paymentMethodCollection
        );
        $request->setCardPaymentMethodSpecificInput($cardPaymentMethodSpecificInput);

        $mobilePaymentMethodSpecificInput = new MobilePaymentMethodSpecificInput();
        $mobilePaymentProduct320SpecificInput = new MobilePaymentProduct320SpecificInput();
        $gPayThreeDSecure = new GPayThreeDSecure();

        $threeDSecure = $cardPaymentMethodSpecificInput->getThreeDSecure();
        $gPayThreeDSecure->setSkipAuthentication($threeDSecure->getSkipAuthentication());
        $gPayThreeDSecure->setChallengeIndicator($threeDSecure->getchallengeIndicator());
        $gPayThreeDSecure->setRedirectionData($threeDSecure->getRedirectionData());
        $gPayThreeDSecure->setExemptionRequest($threeDSecure->getexemptionRequest());

        $mobilePaymentProduct320SpecificInput->setThreeDSecure($gPayThreeDSecure);
        $mobilePaymentMethodSpecificInput->setPaymentProduct320SpecificInput($mobilePaymentProduct320SpecificInput);
        $request->setMobilePaymentMethodSpecificInput($mobilePaymentMethodSpecificInput);

        $redirectPaymentMethodSpecificInput = new RedirectPaymentMethodSpecificInput();
        $redirectPaymentProduct5402SpecificInput = new RedirectPaymentProduct5402SpecificInput();
        $redirectPaymentProduct5402SpecificInput->setCompleteRemainingPaymentAmount(true);

        $redirectPaymentMethodSpecificInput->setPaymentProduct5402SpecificInput($redirectPaymentProduct5402SpecificInput);

        $request->setRedirectPaymentMethodSpecificInput($redirectPaymentMethodSpecificInput);

        return $request;
    }

    private static function getExpirationDate(?DateTime $requestExpiresAt, PayByLinkExpirationTime $expirationTime): DateTime
    {
        if ($requestExpiresAt) {
            $expiresAt = $requestExpiresAt;
        } else {
            $expiresAt = new DateTime('now', new DateTimeZone('UTC'));
            $expiresAt->add(new DateInterval('P' . $expirationTime->getDays() . 'D'));
        }

        $expiresAt->setTime(23, 59, 59);

        return $expiresAt;
    }
}