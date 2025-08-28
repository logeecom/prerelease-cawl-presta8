<?php

namespace OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies;

use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\HostedCheckout\HostedCheckoutSessionRequest;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentResponse;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;

/**
 * Interface HostedCheckoutProxyInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies
 */
interface HostedCheckoutProxyInterface
{
    public function createSession(
        HostedCheckoutSessionRequest $request,
        CardsSettings $cardsSettings,
        PaymentSettings $paymentSettings,
        ?Token $token = null
    ): PaymentResponse;
}