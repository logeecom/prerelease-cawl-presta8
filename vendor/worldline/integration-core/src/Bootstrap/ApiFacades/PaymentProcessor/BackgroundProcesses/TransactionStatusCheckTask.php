<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses;

use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PendingTransactionsRepository;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Infrastructure\TaskExecution\Task;

/**
 * Class TransactionStatusCheckTask.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses
 */
class TransactionStatusCheckTask extends Task
{
    public function execute(): void
    {
        foreach ($this->getPendingTransactionsRepository()->get() as $paymentTransaction) {
            StoreContext::getInstance()->setOrigin('fallback');
            CheckoutAPI::get()->payment($paymentTransaction->getStoreId())->updateOrderStatus(
                $paymentTransaction->getPaymentTransaction()->getPaymentId(),
                $paymentTransaction->getPaymentTransaction()->getReturnHmac()
            );

            $this->reportAlive();
        }

        $this->reportProgress(100);
    }

    protected function getPendingTransactionsRepository(): PendingTransactionsRepository
    {
        return ServiceRegister::getService(PendingTransactionsRepository::class);
    }
}