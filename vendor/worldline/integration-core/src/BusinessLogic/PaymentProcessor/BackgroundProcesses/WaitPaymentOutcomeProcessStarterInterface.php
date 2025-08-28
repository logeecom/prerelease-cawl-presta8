<?php

namespace OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses;

use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;

/**
 * Interface WaitPaymentOutcomeProcessStarterInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses
 */
interface WaitPaymentOutcomeProcessStarterInterface
{
    public function startInBackground(PaymentId $paymentId, ?string $returnHmac = null): void;
}