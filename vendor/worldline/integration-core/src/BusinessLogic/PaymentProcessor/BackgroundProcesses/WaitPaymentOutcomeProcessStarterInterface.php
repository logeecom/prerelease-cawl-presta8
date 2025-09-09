<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
/**
 * Interface WaitPaymentOutcomeProcessStarterInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses
 */
interface WaitPaymentOutcomeProcessStarterInterface
{
    public function startInBackground(?PaymentId $paymentId, ?string $returnHmac = null, ?string $merchantReference = null) : void;
}
