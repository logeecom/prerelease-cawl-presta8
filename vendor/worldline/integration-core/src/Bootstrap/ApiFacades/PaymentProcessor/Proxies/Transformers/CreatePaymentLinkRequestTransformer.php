<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use DateInterval;
use DateTime;
use DateTimeZone;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PayByLinkExpirationTime;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PayByLinkSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use CAWL\OnlinePayments\Sdk\Domain\CreatePaymentLinkRequest;
use CAWL\OnlinePayments\Sdk\Domain\GPayThreeDSecure;
use CAWL\OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use CAWL\OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInput;
use CAWL\OnlinePayments\Sdk\Domain\MobilePaymentProduct320SpecificInput;
use CAWL\OnlinePayments\Sdk\Domain\PaymentLinkSpecificInput;
use CAWL\OnlinePayments\Sdk\Domain\PaymentProductFilter;
use CAWL\OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckout;
use CAWL\OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use CAWL\OnlinePayments\Sdk\Domain\RedirectPaymentProduct5402SpecificInput;
/**
 * Class CreatePaymentLinkRequestTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CreatePaymentLinkRequestTransformer
{
    public static function transform(PaymentLinkRequest $input, CardsSettings $cardsSettings, PaymentSettings $paymentSettings, PayByLinkSettings $payByLinkSettings, PaymentMethodCollection $paymentMethodCollection) : CreatePaymentLinkRequest
    {
        $cart = $input->getCartProvider()->get();
        $request = new CreatePaymentLinkRequest();
        $specificInput = new PaymentLinkSpecificInput();
        $specificInput->setExpirationDate(self::getExpirationDate($input->getExpiresAt(), $payByLinkSettings->getExpirationTime()));
        $request->setPaymentLinkSpecificInput($specificInput);
        $hostedCheckoutSpecificInput = new HostedCheckoutSpecificInput();
        $hostedCheckoutSpecificInput->setReturnUrl($input->getReturnUrl());
        $filters = new PaymentProductFiltersHostedCheckout();
        $productFilter = new PaymentProductFilter();
        $productFilter->setProducts([(int) PaymentProductId::mealvouchers()->getId()]);
        $filters->setExclude($productFilter);
        $hostedCheckoutSpecificInput->setPaymentProductFilters($filters);
        $request->setOrder(OrderTransformer::transform($cart));
        $request->setHostedCheckoutSpecificInput($hostedCheckoutSpecificInput);
        $cardPaymentMethodSpecificInput = CardPaymentMethodSpecificInputTransformer::transform($cart, $input->getReturnUrl(), $cardsSettings, $paymentSettings, $paymentMethodCollection);
        $request->setCardPaymentMethodSpecificInput($cardPaymentMethodSpecificInput);
        $mobilePaymentMethodSpecificInput = new MobilePaymentMethodSpecificInput();
        $mobilePaymentMethodSpecificInput->setPaymentProduct320SpecificInput(GooglePaySpecificRequestTransformer::transform($cardPaymentMethodSpecificInput));
        $request->setMobilePaymentMethodSpecificInput($mobilePaymentMethodSpecificInput);
        $redirectPaymentMethodSpecificInput = new RedirectPaymentMethodSpecificInput();
        $redirectPaymentProduct5402SpecificInput = new RedirectPaymentProduct5402SpecificInput();
        $redirectPaymentProduct5402SpecificInput->setCompleteRemainingPaymentAmount(\true);
        $redirectPaymentMethodSpecificInput->setPaymentProduct5402SpecificInput($redirectPaymentProduct5402SpecificInput);
        $request->setRedirectPaymentMethodSpecificInput($redirectPaymentMethodSpecificInput);
        return $request;
    }
    private static function getExpirationDate(?DateTime $requestExpiresAt, PayByLinkExpirationTime $expirationTime) : DateTime
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
