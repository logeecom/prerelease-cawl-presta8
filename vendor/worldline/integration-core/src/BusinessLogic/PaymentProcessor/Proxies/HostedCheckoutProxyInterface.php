<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedCheckout\HostedCheckoutSessionRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\ThreeDSSettings\ThreeDSSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
/**
 * Interface HostedCheckoutProxyInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies
 */
interface HostedCheckoutProxyInterface
{
    public function createSession(HostedCheckoutSessionRequest $request, ThreeDSSettings $cardsSettings, PaymentSettings $paymentSettings, PaymentMethodCollection $paymentMethodCollection, array $supportedPaymentMethods, ?Token $token = null) : PaymentResponse;
}
