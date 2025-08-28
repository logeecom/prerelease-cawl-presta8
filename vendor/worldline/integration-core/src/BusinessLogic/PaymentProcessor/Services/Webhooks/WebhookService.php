<?php

namespace OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\Webhooks;

use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentTransactionRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
use OnlinePayments\Core\BusinessLogic\Domain\Webhook\WebhookData;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\Payment\StatusUpdateService;

class WebhookService
{
    protected StatusUpdateService $statusUpdateService;
    private PaymentTransactionRepositoryInterface $paymentTransactionRepository;
    private const PAYMENT_LINK_WEBHOOK_TYPE = 'paymentlink.paid';

    /**
     * @param StatusUpdateService $statusUpdateService
     * @param PaymentTransactionRepositoryInterface $paymentTransactionRepository
     */
    public function __construct(
        StatusUpdateService $statusUpdateService,
        PaymentTransactionRepositoryInterface $paymentTransactionRepository
    ) {
        $this->statusUpdateService = $statusUpdateService;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
    }

    public function process(WebhookData $webhook): void
    {
        $paymentId = PaymentId::parse($webhook->getId());

        if ($webhook->getType() === self::PAYMENT_LINK_WEBHOOK_TYPE) {
            $this->processPaymentLink($webhook, $paymentId);

            return;
        }

        if (!$this->paymentTransactionRepository->get($paymentId)) {
            return;
        }

        $this->statusUpdateService->updateOrderStatus($paymentId);
    }

    private function processPaymentLink(WebhookData $webhook, PaymentId $paymentId): void
    {
        $arrayBody = json_decode($webhook->getWebhookBody(), true);

        if (empty($arrayBody) || !isset($arrayBody['paymentLink'])) {
            return;
        }

        $paymentTransaction = $this->paymentTransactionRepository->getByPaymentLinkId($arrayBody['paymentLink']['paymentLinkId']);

        if (!$paymentTransaction) {
            return;
        }

        $this->paymentTransactionRepository->updatePaymentId($paymentTransaction, $paymentId);
        $this->statusUpdateService->updateOrderStatus($paymentId);
    }
}
