<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\PaymentLinks;

use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\PayByLinkSettingsRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PayByLinkSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\CardsSettingsRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentSettingsRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentTransactionRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\Repositories\PaymentLinkRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\PaymentLinksProxyInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\PaymentMethod\PaymentMethodService;
/**
 * Class PaymentLinksService.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\PaymentMethod
 */
class PaymentLinksService
{
    private PaymentLinksProxyInterface $paymentLinksProxy;
    private CardsSettingsRepositoryInterface $cardsSettingsRepository;
    private PaymentSettingsRepositoryInterface $paymentSettingsRepository;
    private PayByLinkSettingsRepositoryInterface $payByLinkSettingsRepository;
    private PaymentLinkRepositoryInterface $paymentLinkRepository;
    private PaymentTransactionRepositoryInterface $paymentTransactionRepository;
    private PaymentMethodService $paymentMethodService;
    public function __construct(PaymentLinksProxyInterface $paymentLinksProxy, CardsSettingsRepositoryInterface $cardsSettingsRepository, PaymentSettingsRepositoryInterface $paymentSettingsRepository, PayByLinkSettingsRepositoryInterface $payByLinkSettingsRepository, PaymentLinkRepositoryInterface $paymentLinkRepository, PaymentTransactionRepositoryInterface $paymentTransactionRepository, PaymentMethodService $paymentMethodService)
    {
        $this->paymentLinksProxy = $paymentLinksProxy;
        $this->cardsSettingsRepository = $cardsSettingsRepository;
        $this->paymentSettingsRepository = $paymentSettingsRepository;
        $this->payByLinkSettingsRepository = $payByLinkSettingsRepository;
        $this->paymentLinkRepository = $paymentLinkRepository;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
        $this->paymentMethodService = $paymentMethodService;
    }
    public function create(PaymentLinkRequest $request) : PaymentLinkResponse
    {
        $response = $this->paymentLinksProxy->create($request, $this->getCardsSettings(), $this->getPaymentSettings(), $this->getPayByLinkSettings(), $this->getPaymentMethods($request->getCartProvider()));
        $this->paymentLinkRepository->save($response->getPaymentLink());
        $this->paymentTransactionRepository->save(PaymentTransaction::createFromPaymentLink($response->getPaymentLink()));
        return $response;
    }
    public function get(string $merchantReference) : PaymentLinkResponse
    {
        $response = $this->paymentLinkRepository->getByMerchantReference($merchantReference);
        if ($response && (!$response->getExpiresAt() || $response->getExpiresAt() < new \DateTime())) {
            return new PaymentLinkResponse(null);
        }
        return new PaymentLinkResponse($response);
    }
    private function getCardsSettings() : CardsSettings
    {
        $savedSettings = $this->cardsSettingsRepository->getCardsSettings();
        return $savedSettings ?: new CardsSettings();
    }
    private function getPaymentSettings() : PaymentSettings
    {
        $savedSettings = $this->paymentSettingsRepository->getPaymentSettings();
        return $savedSettings ?: new PaymentSettings();
    }
    private function getPayByLinkSettings() : PayByLinkSettings
    {
        $savedSettings = $this->payByLinkSettingsRepository->getPayByLinkSettings();
        return $savedSettings ?: new PayByLinkSettings();
    }
    private function getPaymentMethods(CartProvider $cartProvider) : PaymentMethodCollection
    {
        return $this->paymentMethodService->getAvailablePaymentMethods($cartProvider);
    }
}
