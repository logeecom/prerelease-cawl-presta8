<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Controller;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentTransactionRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response\PaymentOutcomeResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response\PaymentTransactionResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Response\UpdateStatusResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses\WaitPaymentOutcomeProcess;
/**
 * Class PaymentController.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Controller
 * @internal
 */
class PaymentController
{
    private WaitPaymentOutcomeProcess $waitPaymentOutcomeProcess;
    private PaymentTransactionRepositoryInterface $paymentTransactionRepository;
    public function __construct(WaitPaymentOutcomeProcess $waitPaymentOutcomeProcess, PaymentTransactionRepositoryInterface $paymentTransactionRepository)
    {
        $this->waitPaymentOutcomeProcess = $waitPaymentOutcomeProcess;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
    }
    public function startWaitingForOutcome(?PaymentId $paymentId, ?string $returnHmac = null, ?string $merchantReference = null) : void
    {
        StoreContext::getInstance()->setOrigin('landing');
        if (!$paymentId && $merchantReference) {
            $this->waitPaymentOutcomeProcess->startWaitingForPaymentLink($merchantReference);
            return;
        }
        $this->waitPaymentOutcomeProcess->startWaiting($paymentId, $returnHmac);
    }
    public function startWaitingForOutcomeInBackground(?PaymentId $paymentId, ?string $returnHmac = null, ?string $merchantReference = null) : void
    {
        StoreContext::getInstance()->setOrigin('landing');
        $this->waitPaymentOutcomeProcess->startInBackground($paymentId, $returnHmac, $merchantReference);
    }
    public function getPaymentOutcome(?PaymentId $paymentId, ?string $returnHmac = null, ?string $merchantReference = null) : PaymentOutcomeResponse
    {
        StoreContext::getInstance()->setOrigin('landing');
        return new PaymentOutcomeResponse($this->waitPaymentOutcomeProcess->getPaymentOutcome($paymentId, $returnHmac, $merchantReference));
    }
    public function updateOrderStatus(PaymentId $paymentId, ?string $returnHmac = null) : UpdateStatusResponse
    {
        $this->waitPaymentOutcomeProcess->updateOrderStatus($paymentId, $returnHmac);
        return new UpdateStatusResponse();
    }
    public function getPaymentTransaction(string $merchantReference) : PaymentTransactionResponse
    {
        return new PaymentTransactionResponse($this->paymentTransactionRepository->getByMerchantReference($merchantReference));
    }
}
