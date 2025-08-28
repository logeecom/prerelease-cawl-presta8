<?php

namespace OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses;

use OnlinePayments\Core\Bootstrap\ApiFacades\Order\OrderAPI\OrderAPI;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\AuthorizedTransactionsRepository;
use OnlinePayments\Core\BusinessLogic\Domain\Capture\CaptureRequest;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Infrastructure\TaskExecution\Task;

/**
 * Class AutoCaptureCheckTask.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses
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
            OrderAPI::get()->capture($paymentTransaction->getStoreId())->handle(
                new CaptureRequest($paymentTransaction->getPaymentTransaction()->getPaymentId())
            );
        }

        $this->reportProgress(100);
    }

    protected function getAuthorizedTransactionsRepository(): AuthorizedTransactionsRepository
    {
        return ServiceRegister::getService(AuthorizedTransactionsRepository::class);
    }
}