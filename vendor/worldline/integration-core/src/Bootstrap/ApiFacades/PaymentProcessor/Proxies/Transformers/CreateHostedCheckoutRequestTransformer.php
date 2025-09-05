<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\HostedCheckout\HostedCheckoutSessionRequest;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputForHostedCheckout;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\CreateMandateRequest;
use OnlinePayments\Sdk\Domain\GPayThreeDSecure;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\MobilePaymentProduct320SpecificInput;
use OnlinePayments\Sdk\Domain\Order;
use OnlinePayments\Sdk\Domain\PaymentProductFilter;
use OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckout;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\RedirectPaymentProduct5402SpecificInput;
use OnlinePayments\Sdk\Domain\RedirectPaymentProduct5408SpecificInput;
use OnlinePayments\Sdk\Domain\SepaDirectDebitPaymentMethodSpecificInputBase;
use OnlinePayments\Sdk\Domain\SepaDirectDebitPaymentProduct771SpecificInputBase;
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
        PaymentMethodCollection $paymentMethodCollection,
        ?Token $token = null
    ): CreateHostedCheckoutRequest {
        $cart = $input->getCartProvider()->get();

        $request = new CreateHostedCheckoutRequest();

        $hostedCheckoutSpecificInput = new HostedCheckoutSpecificInput();
        $hostedCheckoutSpecificInput->setReturnUrl($input->getReturnUrl());

        $paymentProductId = $input->getPaymentProductId() ?: PaymentProductId::hostedCheckout();

        if ($config = $paymentMethodCollection->get($paymentProductId)) {
            $hostedCheckoutSpecificInput->setVariant($config->getTemplate());
        }

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
            $paymentMethodCollection,
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

        if ($input->getPaymentProductId() !== null && $input->getPaymentProductId()->equals(PaymentProductId::mealvouchers())) {
            $redirectPaymentProduct5402SpecificInput = new RedirectPaymentProduct5402SpecificInput();
            $redirectPaymentProduct5402SpecificInput->setCompleteRemainingPaymentAmount(true);
            $redirectPaymentMethodSpecificInput->setPaymentProduct5402SpecificInput($redirectPaymentProduct5402SpecificInput);
        }

        if (null !== $input->getPaymentProductId() &&
            $input->getPaymentProductId()->equals(PaymentProductId::illicado()->getId())) {
            $redirectPaymentMethodSpecificInput->setRequiresApproval(false);
        }

        if ($input->getPaymentProductId() !== null && $input->getPaymentProductId()->isRedirectType()) {
            $redirectPaymentMethodSpecificInput->setPaymentProductId((int)$input->getPaymentProductId()->getId());
        }

        self::setHostedCheckoutSpecificInput($paymentMethodCollection, $hostedCheckoutSpecificInput);
        self::setIntersolveSpecificInput($paymentMethodCollection, $hostedCheckoutSpecificInput);
        self::setSepaSpecificInput($paymentMethodCollection, $order, $request);
        self::setBankTransferSpecificInput($paymentMethodCollection, $redirectPaymentMethodSpecificInput);
        self::setOneySpecificInput($input, $paymentMethodCollection, $redirectPaymentMethodSpecificInput);

        $request->setRedirectPaymentMethodSpecificInput($redirectPaymentMethodSpecificInput);

        return $request;
    }

    /**
     * @param PaymentMethodCollection $paymentMethodCollection
     * @param HostedCheckoutSpecificInput $hostedCheckoutSpecificInput
     *
     * @return void
     */
    protected static function setHostedCheckoutSpecificInput(
        PaymentMethodCollection $paymentMethodCollection,
        HostedCheckoutSpecificInput $hostedCheckoutSpecificInput
    ): void {
        if ($config = $paymentMethodCollection->get(PaymentProductId::hostedCheckout())) {
            $cardSpecificInputForHostedCheckout = new CardPaymentMethodSpecificInputForHostedCheckout();
            $cardSpecificInputForHostedCheckout->setGroupCards($config->getAdditionalData()->isEnableGroupCards());
            $hostedCheckoutSpecificInput->setCardPaymentMethodSpecificInput($cardSpecificInputForHostedCheckout);
        }
    }

    /**
     * @param PaymentMethodCollection $paymentMethodCollection
     * @param HostedCheckoutSpecificInput $hostedCheckoutSpecificInput
     *
     * @return void
     */
    protected static function setIntersolveSpecificInput(
        PaymentMethodCollection $paymentMethodCollection,
        HostedCheckoutSpecificInput $hostedCheckoutSpecificInput
    ): void {
        if ($config = $paymentMethodCollection->get(PaymentProductId::intersolve())) {
            $hostedCheckoutSpecificInput->setSessionTimeout($config->getAdditionalData()->getSessionTimeout()->getDuration());
        }
    }

    /**
     * @param PaymentMethodCollection $paymentMethodCollection
     * @param Order $order
     * @param CreateHostedCheckoutRequest $request
     *
     * @return void
     */
    protected static function setSepaSpecificInput(
        PaymentMethodCollection $paymentMethodCollection,
        Order $order,
        CreateHostedCheckoutRequest $request
    ): void {
        if ($config = $paymentMethodCollection->get(PaymentProductId::sepaDirectDebit())) {
            $sepaDirectDebit = new SepaDirectDebitPaymentMethodSpecificInputBase();
            $specificInput = new SepaDirectDebitPaymentProduct771SpecificInputBase();
            $mandate = new CreateMandateRequest();
            $mandate->setCustomerReference($order->getCustomer()->getMerchantCustomerId());
            $mandate->setRecurrenceType($config->getAdditionalData()->getRecurrenceType()->getType());
            $mandate->setSignatureType($config->getAdditionalData()->getSignatureType()->getType());
            $specificInput->setMandate($mandate);
            $sepaDirectDebit->paymentProduct771SpecificInput = $specificInput;
            $request->setSepaDirectDebitPaymentMethodSpecificInput($sepaDirectDebit);
        }
    }

    /**
     * @param PaymentMethodCollection $paymentMethodCollection
     * @param RedirectPaymentMethodSpecificInput $redirectPaymentMethodSpecificInput
     *
     * @return void
     */
    protected static function setBankTransferSpecificInput(
        PaymentMethodCollection $paymentMethodCollection,
        RedirectPaymentMethodSpecificInput $redirectPaymentMethodSpecificInput
    ): void {
        if ($config = $paymentMethodCollection->get(PaymentProductId::bankTransfer())) {
            $paymentProduct5408SpecificInput = new RedirectPaymentProduct5408SpecificInput();
            $paymentProduct5408SpecificInput->setInstantPaymentOnly($config->getAdditionalData()->isInstantPayment());
            $redirectPaymentMethodSpecificInput->setPaymentProduct5408SpecificInput($paymentProduct5408SpecificInput);
        }
    }

    /**
     * @param HostedCheckoutSessionRequest $input
     * @param PaymentMethodCollection $paymentMethodCollection
     * @param RedirectPaymentMethodSpecificInput $redirectPaymentMethodSpecificInput
     *
     * @return void
     */
    protected static function setOneySpecificInput(
        HostedCheckoutSessionRequest $input,
        PaymentMethodCollection $paymentMethodCollection,
        RedirectPaymentMethodSpecificInput $redirectPaymentMethodSpecificInput
    ): void {
        if (!$input->getPaymentProductId()) {
            return;
        }

        if ($input->getPaymentProductId()->equals(PaymentProductId::ONEY_3X) &&
            $config = $paymentMethodCollection->get(PaymentProductId::oney3x())) {
            $redirectPaymentMethodSpecificInput->setPaymentOption($config->getAdditionalData()->getPaymentOption());
        }

        if ($input->getPaymentProductId()->equals(PaymentProductId::ONEY_4X) &&
            $config = $paymentMethodCollection->get(PaymentProductId::oney4x())) {
            $redirectPaymentMethodSpecificInput->setPaymentOption($config->getAdditionalData()->getPaymentOption());
        }

        if ($input->getPaymentProductId()->equals(PaymentProductId::ONEY_FINANCEMENT_LONG) &&
            $config = $paymentMethodCollection->get(PaymentProductId::oneyFinancementLong())) {
            $redirectPaymentMethodSpecificInput->setPaymentOption($config->getAdditionalData()->getPaymentOption());
        }

        if ($input->getPaymentProductId()->equals(PaymentProductId::ONEY_BANK_CARD) &&
            $config = $paymentMethodCollection->get(PaymentProductId::oneyBankCard())) {
            $redirectPaymentMethodSpecificInput->setPaymentOption($config->getAdditionalData()->getPaymentOption());
        }
    }
}