<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\PaymentResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Payment;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentDetails;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
/**
 * Interface PaymentsProxyInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies
 * @internal
 */
interface PaymentsProxyInterface
{
    public function create(PaymentRequest $request, CardsSettings $cardsSettings, PaymentSettings $paymentSettings, ?Token $token = null) : PaymentResponse;
    public function getPaymentDetails(PaymentId $paymentId) : PaymentDetails;
    public function getPayment(PaymentId $paymentId) : Payment;
    public function getRefunds(PaymentId $paymentId) : array;
    public function getCaptures(PaymentId $paymentId) : array;
}
