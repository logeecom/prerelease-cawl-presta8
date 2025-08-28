<?php

namespace OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies;

use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentRequest;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentResponse;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\Payment;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentDetails;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;

/**
 * Interface PaymentsProxyInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies
 */
interface PaymentsProxyInterface
{
    public function create(
        PaymentRequest $request, CardsSettings $cardsSettings, PaymentSettings $paymentSettings, ?Token $token = null
    ): PaymentResponse;
    public function getPaymentDetails(PaymentId $paymentId): PaymentDetails;
    public function getPayment(PaymentId $paymentId): Payment;
    public function getRefunds(PaymentId $paymentId): array;
    public function getCaptures(PaymentId $paymentId): array;
}