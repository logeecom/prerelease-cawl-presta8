<?php

namespace OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses;

use OnlinePayments\Core\BusinessLogic\Domain\Payment\Exceptions\PaymentTransactionNotFoundException;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentTransactionRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Time\TimeProviderInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslatableLabel;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\Payment\StatusUpdateService;

/**
 * Class WaitPaymentOutcomeProcess.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses
 */
class WaitPaymentOutcomeProcess
{
    /**
     * Sleep interval in seconds between two consecutive payment transaction checks.
     */
    private const SLEEP_INTERVAL = 5;

    private PaymentTransactionRepositoryInterface $paymentTransactionRepository;
    private StatusUpdateService $statusUpdateService;
    private TimeProviderInterface $timeProvider;
    private WaitPaymentOutcomeProcessStarterInterface $waitPaymentOutcomeProcessStarter;

    public function __construct(
        PaymentTransactionRepositoryInterface $paymentTransactionRepository,
        StatusUpdateService $statusUpdateService,
        TimeProviderInterface $timeProvider,
        WaitPaymentOutcomeProcessStarterInterface  $waitPaymentOutcomeProcessStarter
    ) {
        $this->paymentTransactionRepository = $paymentTransactionRepository;
        $this->statusUpdateService = $statusUpdateService;
        $this->timeProvider = $timeProvider;
        $this->waitPaymentOutcomeProcessStarter = $waitPaymentOutcomeProcessStarter;
    }

    public function startInBackground(PaymentId $paymentId, ?string $returnHmac): void
    {
        $this->resetTransactionReturnedAtDate($paymentId, $returnHmac);
        $this->waitPaymentOutcomeProcessStarter->startInBackground($paymentId, $returnHmac);
    }

    public function startWaiting(PaymentId $paymentId, ?string $returnHmac = null): void
    {
        $this->resetTransactionReturnedAtDate($paymentId, $returnHmac);

        $paymentOutcome = $this->getPaymentOutcome($paymentId, $returnHmac);
        while ($paymentOutcome->isWaiting()) {
            $this->timeProvider->sleep(self::SLEEP_INTERVAL);
            $paymentOutcome = $this->getPaymentOutcome($paymentId, $returnHmac);
        }

        $this->statusUpdateService->updateOrderStatus($paymentId, $returnHmac);
    }

    public function getPaymentOutcome(PaymentId $paymentId, ?string $returnHmac = null): WaitPaymentOutcome
    {
        $paymentTransaction = $this->getPaymentTransaction($paymentId, $returnHmac);

        $paymentOutcome = $this->statusUpdateService->getPaymentOutcome($paymentTransaction);

        // If someone checks the status but it is still pending but not waiting run status update from API
        if (!$paymentOutcome->isWaiting() && $paymentOutcome->getStatusCode()->isPending()) {
            $this->statusUpdateService->updateOrderStatus($paymentId, $returnHmac);
        }

        return $paymentOutcome;
    }

    public function updateOrderStatus(PaymentId $paymentId, ?string $returnHmac = null): void
    {
        $this->statusUpdateService->updateOrderStatus($paymentId, $returnHmac);
    }

    private function resetTransactionReturnedAtDate(PaymentId $paymentId, ?string $returnHmac = null): void
    {
        $paymentTransaction = $this->getPaymentTransaction($paymentId, $returnHmac);
        $paymentTransaction->setReturnedAt($this->timeProvider->getCurrentLocalTime());

        $this->paymentTransactionRepository->save($paymentTransaction);
    }

    private function getPaymentTransaction(PaymentId $paymentId, ?string $returnHmac = null): PaymentTransaction
    {
        $paymentTransaction = $this->paymentTransactionRepository->get($paymentId, $returnHmac);
        if (!$paymentTransaction) {
            throw new PaymentTransactionNotFoundException(
                new TranslatableLabel(
                    "Payment transaction for payment ID $paymentId not found.",
                    'PaymentProcessor.paymentTransactionNotFound',
                    [(string)$paymentId]
                )
            );
        }

        return $paymentTransaction;
    }
}