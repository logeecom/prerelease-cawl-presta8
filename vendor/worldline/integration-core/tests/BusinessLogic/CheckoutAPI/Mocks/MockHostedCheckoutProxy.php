<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks;

use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\HostedCheckout\HostedCheckoutSessionRequest;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentResponse;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\HostedCheckoutProxyInterface;

/**
 * Class MockHostedTokenizationProxy.
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks
 */
class MockHostedCheckoutProxy implements HostedCheckoutProxyInterface
{
    private PaymentResponse $paymentResponse;

    public function __construct(PaymentResponse $paymentResponse)
    {
        $this->paymentResponse = $paymentResponse;
    }

    public function createSession(
        HostedCheckoutSessionRequest $request,
        CardsSettings $cardsSettings,
        PaymentSettings $paymentSettings,
        ?Token $token = null
    ): PaymentResponse {
        return $this->paymentResponse;
    }
}