<?php

namespace OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Controller;

use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentTransactionRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response\PaymentOutcomeResponse;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response\PaymentTransactionResponse;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response\UpdateStatusResponse;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses\WaitPaymentOutcomeProcess;

/**
 * Class PaymentController.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Controller
 */
class PaymentController
{
    private WaitPaymentOutcomeProcess $waitPaymentOutcomeProcess;
    private PaymentTransactionRepositoryInterface $paymentTransactionRepository;

    public function __construct(
        WaitPaymentOutcomeProcess $waitPaymentOutcomeProcess,
        PaymentTransactionRepositoryInterface $paymentTransactionRepository
    ) {
        $this->waitPaymentOutcomeProcess = $waitPaymentOutcomeProcess;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
    }

    public function startWaitingForOutcome(PaymentId $paymentId, ?string $returnHmac = null): void
    {
        StoreContext::getInstance()->setOrigin('landing');
        $this->waitPaymentOutcomeProcess->startWaiting($paymentId, $returnHmac);
    }

    public function startWaitingForOutcomeInBackground(PaymentId $paymentId, ?string $returnHmac = null): void
    {
        StoreContext::getInstance()->setOrigin('landing');
        $this->waitPaymentOutcomeProcess->startInBackground($paymentId, $returnHmac);
    }

    public function getPaymentOutcome(PaymentId $paymentId, ?string $returnHmac = null): PaymentOutcomeResponse
    {
        StoreContext::getInstance()->setOrigin('landing');
        return new PaymentOutcomeResponse($this->waitPaymentOutcomeProcess->getPaymentOutcome($paymentId, $returnHmac));
    }

    public function updateOrderStatus(PaymentId $paymentId, ?string $returnHmac = null): UpdateStatusResponse
    {
        $this->waitPaymentOutcomeProcess->updateOrderStatus($paymentId, $returnHmac);

        return new UpdateStatusResponse();
    }

    public function getPaymentTransaction(string $merchantReference): PaymentTransactionResponse
    {
        $transactions = $this->paymentTransactionRepository->getByMerchantReference($merchantReference);
        $transaction = !empty($transactions) ? $transactions[0] : null;

        return new PaymentTransactionResponse($transaction);
    }
}