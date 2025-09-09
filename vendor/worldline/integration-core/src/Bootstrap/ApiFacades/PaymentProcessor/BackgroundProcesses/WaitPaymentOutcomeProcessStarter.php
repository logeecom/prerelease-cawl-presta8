<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses\WaitPaymentOutcomeProcessStarterInterface;
use CAWL\OnlinePayments\Core\Infrastructure\Logger\Logger;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
/**
 * Interface WaitPaymentOutcomeProcessStarter.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses
 * @internal
 */
class WaitPaymentOutcomeProcessStarter implements WaitPaymentOutcomeProcessStarterInterface
{
    private AsyncProcessService $asyncProcessService;
    private StoreContext $storeContext;
    public function __construct(AsyncProcessService $asyncProcessService, StoreContext $storeContext)
    {
        $this->asyncProcessService = $asyncProcessService;
        $this->storeContext = $storeContext;
    }
    public function startInBackground(?PaymentId $paymentId, ?string $returnHmac = null, ?string $merchantReference = null) : void
    {
        try {
            $this->asyncProcessService->start(new WaitPaymentOutcomeProcessRunner($paymentId, $returnHmac, $merchantReference, $this->storeContext->getStoreId()));
        } catch (\Throwable $e) {
            Logger::logError('Unhandled error occurred during waiting payment outcome process starting in the background.', 'Core.WaitPaymentOutcomeProcessStarter', ['message' => $e->getMessage(), 'type' => \get_class($e), 'trace' => $e->getTraceAsString()]);
        }
    }
}
