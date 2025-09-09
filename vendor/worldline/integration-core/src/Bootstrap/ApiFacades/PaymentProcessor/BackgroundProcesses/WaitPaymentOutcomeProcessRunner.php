<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses;

use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use CAWL\OnlinePayments\Core\Infrastructure\Serializer\Interfaces\Serializable;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\Runnable;
/**
 * Class WaitPaymentOutcomeProcessRunner.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses
 * @internal
 */
class WaitPaymentOutcomeProcessRunner implements Runnable
{
    private PaymentId $paymentId;
    private ?string $returnHmac;
    private ?string $merchantReference;
    private string $storeId;
    public function __construct(PaymentId $paymentId, ?string $returnHmac, ?string $merchantReference, string $storeId)
    {
        $this->paymentId = $paymentId;
        $this->returnHmac = $returnHmac;
        $this->merchantReference = $merchantReference;
        $this->storeId = $storeId;
    }
    public function run() : void
    {
        CheckoutAPI::get()->payment($this->storeId)->startWaitingForOutcome($this->paymentId, $this->returnHmac, $this->merchantReference);
    }
    public static function fromArray(array $array) : Serializable
    {
        return new WaitPaymentOutcomeProcessRunner(PaymentId::parse($array['paymentId']), $array['returnHmac'], $array['merchantReference'], $array['storeId']);
    }
    public function toArray() : array
    {
        return ['paymentId' => (string) $this->paymentId, 'returnHmac' => $this->returnHmac, 'merchantReference' => $this->merchantReference, 'storeId' => $this->storeId];
    }
}
