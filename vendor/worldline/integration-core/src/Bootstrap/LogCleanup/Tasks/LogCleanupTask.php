<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\LogCleanup\Tasks;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\MonitoringLogRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\WebhookLogRepositoryInterface;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Task;
/**
 * Class LogCleanupTask
 *
 * @package OnlinePayments\Core\Bootstrap\LogCleanup\Tasks
 * @internal
 */
class LogCleanupTask extends Task
{
    /**
     * @inheritDoc
     */
    public function execute() : void
    {
        $repository = $this->getMonitoringLogRepository();
        $this->deleteLogs($repository);
        $this->reportProgress(50);
        $repository = $this->getWebhookLogRepository();
        $this->deleteLogs($repository);
        $this->reportProgress(100);
    }
    /**
     * @param MonitoringLogRepositoryInterface | WebhookLogRepositoryInterface $repository
     *
     * @return void
     */
    protected function deleteLogs($repository) : void
    {
        while ($repository->countExpired() > 0) {
            $repository->deleteExpired();
            $this->reportAlive();
        }
    }
    /**
     * @return MonitoringLogRepositoryInterface
     */
    protected function getMonitoringLogRepository() : MonitoringLogRepositoryInterface
    {
        return ServiceRegister::getService(MonitoringLogRepositoryInterface::class);
    }
    /**
     * @return WebhookLogRepositoryInterface
     */
    protected function getWebhookLogRepository() : WebhookLogRepositoryInterface
    {
        return ServiceRegister::getService(WebhookLogRepositoryInterface::class);
    }
}
