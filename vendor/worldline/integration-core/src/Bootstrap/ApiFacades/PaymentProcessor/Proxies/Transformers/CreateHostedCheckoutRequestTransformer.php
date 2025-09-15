<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAction;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedCheckout\HostedCheckoutSessionRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use CAWL\OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputForHostedCheckout;
use CAWL\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use CAWL\OnlinePayments\Sdk\Domain\CreateMandateRequest;
use CAWL\OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use CAWL\OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInput;
use CAWL\OnlinePayments\Sdk\Domain\Order;
use CAWL\OnlinePayments\Sdk\Domain\PaymentProductFilter;
use CAWL\OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckout;
use CAWL\OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use CAWL\OnlinePayments\Sdk\Domain\RedirectPaymentProduct5402SpecificInput;
use CAWL\OnlinePayments\Sdk\Domain\RedirectPaymentProduct5403SpecificInput;
use CAWL\OnlinePayments\Sdk\Domain\RedirectPaymentProduct5408SpecificInput;
use CAWL\OnlinePayments\Sdk\Domain\SepaDirectDebitPaymentMethodSpecificInputBase;
use CAWL\OnlinePayments\Sdk\Domain\SepaDirectDebitPaymentProduct771SpecificInputBase;
use CAWL\OnlinePayments\Sdk\Domain\SurchargeSpecificInput;
/**
 * Class CreateHostedCheckoutRequestTransformer.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\Transformers
 */
class CreateHostedCheckoutRequestTransformer
{
    public static function transform(HostedCheckoutSessionRequest $input, CardsSettings $cardsSettings, PaymentSettings $paymentSettings, PaymentMethodCollection $paymentMethodCollection, array $supportedPaymentMethods, ?Token $token = null) : CreateHostedCheckoutRequest
    {
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
        $productFilter->setProducts(\array_map('intval', $supportedPaymentMethods));
        if (null !== $input->getPaymentProductId()) {
            $productFilter->setProducts([(int) $input->getPaymentProductId()->getId()]);
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
        $cardPaymentMethodSpecificInput = CardPaymentMethodSpecificInputTransformer::transform($cart, $input->getReturnUrl(), $cardsSettings, $paymentSettings, $paymentMethodCollection, $input->getPaymentProductId(), $token);
        $request->setCardPaymentMethodSpecificInput($cardPaymentMethodSpecificInput);
        $mobilePaymentMethodSpecificInput = new MobilePaymentMethodSpecificInput();
        $mobilePaymentMethodSpecificInput->setAuthorizationMode($paymentSettings->getPaymentAction()->getType());
        if (null !== $input->getPaymentProductId() && $input->getPaymentProductId()->isMobileType()) {
            $mobilePaymentMethodSpecificInput->setPaymentProductId($input->getPaymentProductId()->getId());
        }
        $mobilePaymentMethodSpecificInput->setPaymentProduct320SpecificInput(GooglePaySpecificRequestTransformer::transform($cardPaymentMethodSpecificInput));
        $request->setMobilePaymentMethodSpecificInput($mobilePaymentMethodSpecificInput);
        $redirectPaymentMethodSpecificInput = new RedirectPaymentMethodSpecificInput();
        $redirectPaymentMethodSpecificInput->setRequiresApproval(PaymentAction::authorize()->equals($paymentSettings->getPaymentAction()));
        if ($input->getPaymentProductId() !== null && $input->getPaymentProductId()->equals(PaymentProductId::mealvouchers())) {
            $redirectPaymentProduct5402SpecificInput = new RedirectPaymentProduct5402SpecificInput();
            $redirectPaymentProduct5402SpecificInput->setCompleteRemainingPaymentAmount(\true);
            $redirectPaymentMethodSpecificInput->setPaymentProduct5402SpecificInput($redirectPaymentProduct5402SpecificInput);
            $redirectPaymentMethodSpecificInput->setRequiresApproval(\false);
            // Reset mobile specific input because it breaks mealvouchers
            $request->setMobilePaymentMethodSpecificInput(null);
        }
        if ($input->getPaymentProductId() !== null && $input->getPaymentProductId()->equals(PaymentProductId::chequeVacancesConnect())) {
            $redirectPaymentProduct5403SpecificInput = new RedirectPaymentProduct5403SpecificInput();
            $redirectPaymentProduct5403SpecificInput->setCompleteRemainingPaymentAmount(\true);
            $redirectPaymentMethodSpecificInput->setPaymentProduct5403SpecificInput($redirectPaymentProduct5403SpecificInput);
            $redirectPaymentMethodSpecificInput->setRequiresApproval(\false);
            $request->setMobilePaymentMethodSpecificInput(null);
        }
        if ($input->getPaymentProductId() !== null && $input->getPaymentProductId()->equals(PaymentProductId::intersolve())) {
            // Reset mobile specific input because it breaks intersolve
            $request->setMobilePaymentMethodSpecificInput(null);
        }
        if (null !== $input->getPaymentProductId() && $input->getPaymentProductId()->equals(PaymentProductId::illicado()->getId())) {
            $redirectPaymentMethodSpecificInput->setRequiresApproval(\false);
        }
        if ($input->getPaymentProductId() !== null && $input->getPaymentProductId()->isRedirectType()) {
            $redirectPaymentMethodSpecificInput->setPaymentProductId((int) $input->getPaymentProductId()->getId());
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
    protected static function setHostedCheckoutSpecificInput(PaymentMethodCollection $paymentMethodCollection, HostedCheckoutSpecificInput $hostedCheckoutSpecificInput) : void
    {
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
    protected static function setIntersolveSpecificInput(PaymentMethodCollection $paymentMethodCollection, HostedCheckoutSpecificInput $hostedCheckoutSpecificInput) : void
    {
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
    protected static function setSepaSpecificInput(PaymentMethodCollection $paymentMethodCollection, Order $order, CreateHostedCheckoutRequest $request) : void
    {
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
    protected static function setBankTransferSpecificInput(PaymentMethodCollection $paymentMethodCollection, RedirectPaymentMethodSpecificInput $redirectPaymentMethodSpecificInput) : void
    {
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
    protected static function setOneySpecificInput(HostedCheckoutSessionRequest $input, PaymentMethodCollection $paymentMethodCollection, RedirectPaymentMethodSpecificInput $redirectPaymentMethodSpecificInput) : void
    {
        if (!$input->getPaymentProductId()) {
            return;
        }
        if ($input->getPaymentProductId()->equals(PaymentProductId::ONEY_3X) && ($config = $paymentMethodCollection->get(PaymentProductId::oney3x()))) {
            $redirectPaymentMethodSpecificInput->setPaymentOption($config->getAdditionalData()->getPaymentOption());
        }
        if ($input->getPaymentProductId()->equals(PaymentProductId::ONEY_4X) && ($config = $paymentMethodCollection->get(PaymentProductId::oney4x()))) {
            $redirectPaymentMethodSpecificInput->setPaymentOption($config->getAdditionalData()->getPaymentOption());
        }
        if ($input->getPaymentProductId()->equals(PaymentProductId::ONEY_FINANCEMENT_LONG) && ($config = $paymentMethodCollection->get(PaymentProductId::oneyFinancementLong()))) {
            $redirectPaymentMethodSpecificInput->setPaymentOption($config->getAdditionalData()->getPaymentOption());
        }
        if ($input->getPaymentProductId()->equals(PaymentProductId::ONEY_BANK_CARD) && ($config = $paymentMethodCollection->get(PaymentProductId::oneyBankCard()))) {
            $redirectPaymentMethodSpecificInput->setPaymentOption($config->getAdditionalData()->getPaymentOption());
        }
    }
}
