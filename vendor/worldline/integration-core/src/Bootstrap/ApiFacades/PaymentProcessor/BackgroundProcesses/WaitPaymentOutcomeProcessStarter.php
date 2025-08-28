<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses;

use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses\WaitPaymentOutcomeProcessStarterInterface;
use OnlinePayments\Core\Infrastructure\Logger\Logger;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;

/**
 * Interface WaitPaymentOutcomeProcessStarter.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses
 */
class WaitPaymentOutcomeProcessStarter implements WaitPaymentOutcomeProcessStarterInterface
{
    private AsyncProcessService $asyncProcessService;
    private StoreContext $storeContext;

    public function __construct(AsyncProcessService $asyncProcessService, StoreContext  $storeContext)
    {
        $this->asyncProcessService = $asyncProcessService;
        $this->storeContext = $storeContext;
    }

    public function startInBackground(PaymentId $paymentId, ?string $returnHmac = null): void
    {
        try {
            $this->asyncProcessService->start(
                new WaitPaymentOutcomeProcessRunner($paymentId, $returnHmac, $this->storeContext->getStoreId())
            );
        } catch (\Throwable $e) {
            Logger::logError(
                'Unhandled error occurred during waiting payment outcome process starting in the background.',
                'Core.WaitPaymentOutcomeProcessStarter',
                [
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        }
    }
}