<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses;

use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\Order\OrderAPI\OrderAPI;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\AuthorizedTransactionsRepository;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Capture\CaptureRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Task;
/**
 * Class AutoCaptureCheckTask.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses
 * @internal
 */
class AutoCaptureCheckTask extends Task
{
    /**
     * @inheritDoc
     */
    public function execute()
    {
        foreach ($this->getAuthorizedTransactionsRepository()->get() as $paymentTransaction) {
            StoreContext::getInstance()->setOrigin('order.autocapture');
            OrderAPI::get()->capture($paymentTransaction->getStoreId())->handle(new CaptureRequest($paymentTransaction->getPaymentTransaction()->getPaymentId()));
        }
        $this->reportProgress(100);
    }
    protected function getAuthorizedTransactionsRepository() : AuthorizedTransactionsRepository
    {
        return ServiceRegister::getService(AuthorizedTransactionsRepository::class);
    }
}
