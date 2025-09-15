<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\HostedCheckout;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedCheckout\HostedCheckoutSessionRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Repositories\TokensRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentSettingsRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentTransactionRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\ThreeDSSettings\ThreeDSSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodDefaultConfigs;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\ThreeDSSettingsService;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\HostedCheckoutProxyInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Repositories\ProductTypeRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\PaymentMethod\PaymentMethodService;
/**
 * Class HostedTokenizationService.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\HostedTokenization
 */
class HostedCheckoutService
{
    private HostedCheckoutProxyInterface $hostedCheckoutProxy;
    private PaymentTransactionRepositoryInterface $paymentTransactionRepository;
    private ThreeDSSettingsService $threeDSSettingsService;
    private PaymentSettingsRepositoryInterface $paymentSettingsRepository;
    private TokensRepositoryInterface $tokensRepository;
    private ProductTypeRepositoryInterface $productTypeRepository;
    private PaymentMethodService $paymentMethodService;
    private PaymentProductService $paymentProductService;
    public function __construct(HostedCheckoutProxyInterface $hostedCheckoutProxy, PaymentTransactionRepositoryInterface $paymentTransactionRepository, TokensRepositoryInterface $tokensRepository, ThreeDSSettingsService $threeDSSettingsService, PaymentSettingsRepositoryInterface $paymentSettingsRepository, ProductTypeRepositoryInterface $productTypeRepository, PaymentMethodService $paymentMethodService, PaymentProductService $paymentProductService)
    {
        $this->hostedCheckoutProxy = $hostedCheckoutProxy;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
        $this->tokensRepository = $tokensRepository;
        $this->threeDSSettingsService = $threeDSSettingsService;
        $this->paymentSettingsRepository = $paymentSettingsRepository;
        $this->productTypeRepository = $productTypeRepository;
        $this->paymentMethodService = $paymentMethodService;
        $this->paymentProductService = $paymentProductService;
    }
    public function createSession(HostedCheckoutSessionRequest $request) : PaymentResponse
    {
        $request = $this->transformForMealvouchers($request);
        $token = null;
        if (null !== $request->getTokenId()) {
            $token = $this->tokensRepository->get($request->getCartProvider()->get()->getCustomer()->getMerchantCustomerId(), $request->getTokenId());
        }
        $paymentResponse = $this->hostedCheckoutProxy->createSession($request, $this->getThreeDSSettings($request->getPaymentProductId() ?: PaymentProductId::hostedCheckout()), $this->getPaymentSettings(), $this->getPaymentMethodsConfig($request->getCartProvider()), $this->paymentProductService->getSupportedPaymentMethods(), $token);
        if (!$request->getCartProvider()->get()->getCustomer()->isGuest()) {
            $paymentResponse->getPaymentTransaction()->setCustomerId($request->getCartProvider()->get()->getCustomer()->getMerchantCustomerId());
        }
        if ($request->getPaymentProductId()) {
            $paymentResponse->getPaymentTransaction()->setPaymentMethod(\array_key_exists($request->getPaymentProductId()->getId(), PaymentMethodDefaultConfigs::PAYMENT_METHOD_CONFIGS) ? PaymentMethodDefaultConfigs::PAYMENT_METHOD_CONFIGS[$request->getPaymentProductId()->getId()]['name']['translation'] : '');
        }
        $this->paymentTransactionRepository->save($paymentResponse->getPaymentTransaction());
        return $paymentResponse;
    }
    public function getThreeDSSettings(PaymentProductId $paymentProductId) : ThreeDSSettings
    {
        $savedSettings = $this->threeDSSettingsService->getThreeDSSettings($paymentProductId);
        return $savedSettings ?: new ThreeDSSettings();
    }
    public function getPaymentSettings() : PaymentSettings
    {
        $savedSettings = $this->paymentSettingsRepository->getPaymentSettings();
        return $savedSettings ?: new PaymentSettings();
    }
    public function getPaymentMethodsConfig(CartProvider $cartProvider) : PaymentMethodCollection
    {
        return $this->paymentMethodService->getAvailablePaymentMethods($cartProvider);
    }
    private function transformForMealvouchers(HostedCheckoutSessionRequest $request) : HostedCheckoutSessionRequest
    {
        if (null === $request->getPaymentProductId() || !$request->getPaymentProductId()->equals(PaymentProductId::mealvouchers())) {
            return $request;
        }
        return new HostedCheckoutSessionRequest(new MealvouchersCartProvider($this->productTypeRepository, $request->getCartProvider()), $request->getReturnUrl(), $request->getPaymentProductId(), $request->getTokenId());
    }
}
