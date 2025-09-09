<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses;

use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PendingTransactionsRepository;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Task;
/**
 * Class TransactionStatusCheckTask.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses
 * @internal
 */
class TransactionStatusCheckTask extends Task
{
    public function execute() : void
    {
        foreach ($this->getPendingTransactionsRepository()->get() as $paymentTransaction) {
            StoreContext::getInstance()->setOrigin('fallback');
            CheckoutAPI::get()->payment($paymentTransaction->getStoreId())->updateOrderStatus($paymentTransaction->getPaymentTransaction()->getPaymentId(), $paymentTransaction->getPaymentTransaction()->getReturnHmac());
            $this->reportAlive();
        }
        $this->reportProgress(100);
    }
    protected function getPendingTransactionsRepository() : PendingTransactionsRepository
    {
        return ServiceRegister::getService(PendingTransactionsRepository::class);
    }
}
