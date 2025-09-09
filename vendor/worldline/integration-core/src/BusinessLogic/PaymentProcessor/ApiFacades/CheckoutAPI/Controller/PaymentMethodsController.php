<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Controller;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\MemoryCachingCartProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response\PaymentMethodsResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\HostedTokenization\HostedTokenizationService;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\PaymentMethod\PaymentMethodService;
/**
 * Class PaymentMethodsController.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Controller
 * @internal
 */
class PaymentMethodsController
{
    private PaymentMethodService $paymentMethodService;
    private HostedTokenizationService $hostedTokenizationService;
    public function __construct(PaymentMethodService $paymentMethodService, HostedTokenizationService $hostedTokenizationService)
    {
        $this->paymentMethodService = $paymentMethodService;
        $this->hostedTokenizationService = $hostedTokenizationService;
    }
    public function getAvailablePaymentMethods(CartProvider $cartProvider) : PaymentMethodsResponse
    {
        StoreContext::getInstance()->setOrigin('checkoutLoad');
        $cartProvider = new MemoryCachingCartProvider($cartProvider);
        return new PaymentMethodsResponse($this->paymentMethodService->getAvailablePaymentMethods($cartProvider), $this->hostedTokenizationService->getValidTokens($cartProvider));
    }
}
