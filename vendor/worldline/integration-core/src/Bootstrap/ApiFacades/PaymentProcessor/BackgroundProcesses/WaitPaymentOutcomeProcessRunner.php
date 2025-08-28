<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses;

use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use OnlinePayments\Core\Infrastructure\Serializer\Interfaces\Serializable;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\Runnable;

/**
 * Class WaitPaymentOutcomeProcessRunner.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses
 */
class WaitPaymentOutcomeProcessRunner implements Runnable
{
    private PaymentId $paymentId;
    private ?string $returnHmac;
    private string $storeId;

    public function __construct(PaymentId $paymentId, ?string $returnHmac, string $storeId)
    {
        $this->paymentId = $paymentId;
        $this->returnHmac = $returnHmac;
        $this->storeId = $storeId;
    }

    public function run(): void
    {
        CheckoutAPI::get()->payment($this->storeId)->startWaitingForOutcome($this->paymentId, $this->returnHmac);
    }

    public static function fromArray(array $data): Serializable
    {
        return new WaitPaymentOutcomeProcessRunner(
            PaymentId::parse($data['paymentId']),
            $data['returnHmac'],
            $data['storeId']
        );
    }

    public function toArray(): array
    {
        return [
            'paymentId' => (string)$this->paymentId,
            'returnHmac' => $this->returnHmac,
            'storeId' => $this->storeId,
        ];
    }
}