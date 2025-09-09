<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Exceptions\PaymentTransactionNotFoundException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentTransactionRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\Repositories\PaymentLinkRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Time\TimeProviderInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslatableLabel;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\PaymentLinksProxyInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\Payment\StatusUpdateService;
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
    private PaymentLinkRepositoryInterface $paymentLinkRepository;
    private PaymentLinksProxyInterface $paymentLinksProxy;
    public function __construct(PaymentTransactionRepositoryInterface $paymentTransactionRepository, StatusUpdateService $statusUpdateService, TimeProviderInterface $timeProvider, WaitPaymentOutcomeProcessStarterInterface $waitPaymentOutcomeProcessStarter, PaymentLinkRepositoryInterface $paymentLinkRepository, PaymentLinksProxyInterface $paymentLinksProxy)
    {
        $this->paymentTransactionRepository = $paymentTransactionRepository;
        $this->statusUpdateService = $statusUpdateService;
        $this->timeProvider = $timeProvider;
        $this->waitPaymentOutcomeProcessStarter = $waitPaymentOutcomeProcessStarter;
        $this->paymentLinkRepository = $paymentLinkRepository;
        $this->paymentLinksProxy = $paymentLinksProxy;
    }
    public function startInBackground(?PaymentId $paymentId, ?string $returnHmac, ?string $merchantReference = null) : void
    {
        $this->resetTransaction($paymentId, $returnHmac, $merchantReference);
        $this->waitPaymentOutcomeProcessStarter->startInBackground($paymentId, $returnHmac, $merchantReference);
    }
    public function startWaiting(PaymentId $paymentId, ?string $returnHmac = null) : void
    {
        $this->resetTransactionReturnedAtDate($paymentId, $returnHmac);
        $paymentOutcome = $this->getPaymentOutcome($paymentId, $returnHmac);
        while ($paymentOutcome->isWaiting()) {
            $this->timeProvider->sleep(self::SLEEP_INTERVAL);
            $paymentOutcome = $this->getPaymentOutcome($paymentId, $returnHmac);
        }
        $this->statusUpdateService->updateOrderStatus($paymentId, $returnHmac);
    }
    public function startWaitingForPaymentLink(string $merchantReference) : void
    {
        $paymentId = $this->tryToGetPaymentByLink($merchantReference);
        $this->resetTransactionReturnedAtDateForLink($merchantReference);
        $paymentOutcome = $this->getPaymentOutcome(null, null, $merchantReference);
        while ($paymentOutcome->isWaiting()) {
            $this->timeProvider->sleep(self::SLEEP_INTERVAL);
            $paymentOutcome = $this->getPaymentOutcome(null, null, $merchantReference);
        }
        if ($paymentId) {
            $this->statusUpdateService->updateOrderStatus($paymentId);
        }
    }
    public function getPaymentOutcome(?PaymentId $paymentId, ?string $returnHmac = null, ?string $merchantReference = null) : WaitPaymentOutcome
    {
        $paymentTransaction = $merchantReference ? $this->getPaymentTransactionByMerchantReference($merchantReference) : $this->getPaymentTransaction($paymentId, $returnHmac);
        $paymentOutcome = $this->statusUpdateService->getPaymentOutcome($paymentTransaction);
        // If someone checks the status, but it is still pending but not waiting run status update from API
        if (!$paymentOutcome->isWaiting() && $paymentOutcome->getStatusCode()->isPending()) {
            $this->statusUpdateService->updateOrderStatus($paymentId, $returnHmac);
        }
        return $paymentOutcome;
    }
    public function updateOrderStatus(PaymentId $paymentId, ?string $returnHmac = null) : void
    {
        $this->statusUpdateService->updateOrderStatus($paymentId, $returnHmac);
    }
    private function resetTransactionReturnedAtDate(PaymentId $paymentId, ?string $returnHmac = null) : void
    {
        $paymentTransaction = $this->getPaymentTransaction($paymentId, $returnHmac);
        $paymentTransaction->setReturnedAt($this->timeProvider->getCurrentLocalTime());
        $this->paymentTransactionRepository->save($paymentTransaction);
    }
    private function resetTransactionReturnedAtDateForLink(string $merchantReference) : void
    {
        $paymentTransaction = $this->getPaymentTransactionByMerchantReference($merchantReference);
        $paymentTransaction->setReturnedAt($this->timeProvider->getCurrentLocalTime());
        $this->paymentTransactionRepository->save($paymentTransaction);
    }
    private function resetTransaction(?PaymentId $paymentId, ?string $returnHmac = null, ?string $merchantReference = null) : void
    {
        if (!$paymentId && $merchantReference) {
            $this->resetTransactionReturnedAtDateForLink($merchantReference);
            return;
        }
        $this->resetTransactionReturnedAtDate($paymentId, $returnHmac);
    }
    private function getPaymentTransaction(PaymentId $paymentId, ?string $returnHmac = null) : PaymentTransaction
    {
        $paymentTransaction = $this->paymentTransactionRepository->get($paymentId, $returnHmac);
        if (!$paymentTransaction) {
            throw new PaymentTransactionNotFoundException(new TranslatableLabel("Payment transaction for payment ID {$paymentId} not found.", 'PaymentProcessor.paymentTransactionNotFound', [(string) $paymentId]));
        }
        return $paymentTransaction;
    }
    private function getPaymentTransactionByMerchantReference(string $merchantReference) : PaymentTransaction
    {
        $paymentLink = $this->paymentLinkRepository->getByMerchantReference($merchantReference);
        $paymentTransaction = $this->paymentTransactionRepository->getByPaymentLinkId($paymentLink->getPaymentLinkId());
        if (!$paymentTransaction) {
            throw new PaymentTransactionNotFoundException(new TranslatableLabel("Payment transaction for merchant reference {$merchantReference} not found.", 'PaymentProcessor.paymentTransactionNotFound', [$merchantReference]));
        }
        return $paymentTransaction;
    }
    private function tryToGetPaymentByLink(string $merchantReference) : ?PaymentId
    {
        $paymentLink = $this->paymentLinkRepository->getByMerchantReference($merchantReference);
        $paymentTransaction = $this->paymentTransactionRepository->getByPaymentLinkId($paymentLink->getPaymentLinkId());
        $paymentId = $paymentTransaction->getPaymentId();
        if ($paymentId) {
            return $paymentId;
        }
        $paymentLinkResponse = $this->paymentLinksProxy->getById($paymentLink->getPaymentLinkId(), $merchantReference);
        $paymentId = $paymentLinkResponse->getPaymentLink()->getPaymentId();
        if (!$paymentId) {
            return null;
        }
        $this->paymentTransactionRepository->updatePaymentId($paymentTransaction, $paymentId);
        $this->statusUpdateService->updateOrderStatus($paymentId);
        return $paymentId;
    }
}
