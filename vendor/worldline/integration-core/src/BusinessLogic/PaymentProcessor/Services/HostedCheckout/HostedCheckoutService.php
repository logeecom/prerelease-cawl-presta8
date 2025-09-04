<?php

namespace OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\HostedCheckout;

use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\HostedCheckout\HostedCheckoutSessionRequest;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentResponse;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Repositories\TokensRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\CardsSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentTransactionRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodDefaultConfigs;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\HostedCheckoutProxyInterface;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Repositories\ProductTypeRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\PaymentMethod\PaymentMethodService;

/**
 * Class HostedTokenizationService.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\HostedTokenization
 */
class HostedCheckoutService
{
    private HostedCheckoutProxyInterface $hostedCheckoutProxy;
    private PaymentTransactionRepositoryInterface $paymentTransactionRepository;
    private CardsSettingsRepositoryInterface $cardsSettingsRepository;
    private PaymentSettingsRepositoryInterface $paymentSettingsRepository;
    private TokensRepositoryInterface $tokensRepository;
    private ProductTypeRepositoryInterface $productTypeRepository;
    private PaymentMethodService $paymentMethodService;

    public function __construct(
        HostedCheckoutProxyInterface $hostedCheckoutProxy,
        PaymentTransactionRepositoryInterface $paymentTransactionRepository,
        TokensRepositoryInterface $tokensRepository,
        CardsSettingsRepositoryInterface $cardsSettingsRepository,
        PaymentSettingsRepositoryInterface $paymentSettingsRepository,
        ProductTypeRepositoryInterface $productTypeRepository,
        PaymentMethodService $paymentMethodService
    ) {
        $this->hostedCheckoutProxy = $hostedCheckoutProxy;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
        $this->tokensRepository = $tokensRepository;
        $this->cardsSettingsRepository = $cardsSettingsRepository;
        $this->paymentSettingsRepository = $paymentSettingsRepository;
        $this->productTypeRepository = $productTypeRepository;
        $this->paymentMethodService = $paymentMethodService;
    }

    public function createSession(HostedCheckoutSessionRequest $request): PaymentResponse
    {
        $request = $this->transformForMealvouchers($request);

        $token = null;
        if (null !== $request->getTokenId()) {
            $token = $this->tokensRepository->get(
                $request->getCartProvider()->get()->getCustomer()->getMerchantCustomerId(),
                $request->getTokenId()
            );
        }

        $paymentResponse = $this->hostedCheckoutProxy->createSession(
            $request,
            $this->getCardsSettings(),
            $this->getPaymentSettings(),
            $this->getPaymentMethodsConfig($request->getCartProvider()),
            $token
        );

        if (!$request->getCartProvider()->get()->getCustomer()->isGuest()) {
            $paymentResponse->getPaymentTransaction()->setCustomerId(
                $request->getCartProvider()->get()->getCustomer()->getMerchantCustomerId()
            );
        }

        if ($request->getPaymentProductId()) {
            $paymentResponse->getPaymentTransaction()->setPaymentMethod(
                array_key_exists($request->getPaymentProductId()->getId(), PaymentMethodDefaultConfigs::PAYMENT_METHOD_CONFIGS) ?
                    PaymentMethodDefaultConfigs::PAYMENT_METHOD_CONFIGS[$request->getPaymentProductId()->getId()]['name']['translation']
                    : ''
            );
        }

        $this->paymentTransactionRepository->save($paymentResponse->getPaymentTransaction());

        return $paymentResponse;
    }

    public function getCardsSettings(): CardsSettings
    {
        $savedSettings = $this->cardsSettingsRepository->getCardsSettings();

        return $savedSettings ?: new CardsSettings();
    }

    public function getPaymentSettings(): PaymentSettings
    {
        $savedSettings = $this->paymentSettingsRepository->getPaymentSettings();

        return $savedSettings ?: new PaymentSettings();
    }

    public function getPaymentMethodsConfig(CartProvider $cartProvider): PaymentMethodCollection
    {
        return $this->paymentMethodService->getAvailablePaymentMethods($cartProvider);
    }

    private function transformForMealvouchers(HostedCheckoutSessionRequest $request): HostedCheckoutSessionRequest
    {
        if (
            null === $request->getPaymentProductId() ||
            !$request->getPaymentProductId()->equals(PaymentProductId::mealvouchers())
        ) {
            return $request;
        }

        return new HostedCheckoutSessionRequest(
            new MealvouchersCartProvider(
                $this->productTypeRepository,
                $request->getCartProvider()
            ),
            $request->getReturnUrl(),
            $request->getPaymentProductId(),
            $request->getTokenId()
        );
    }
}