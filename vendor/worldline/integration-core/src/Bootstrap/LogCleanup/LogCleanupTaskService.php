<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\LogCleanup;

use CAWL\OnlinePayments\Core\Bootstrap\LogCleanup\Tasks\LogCleanupTask;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\LogCleanup\LogCleanupTaskServiceInterface;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\QueueService;
/**
 * Class LogCleanupTaskEnqueuer
 *
 * @package OnlinePayments\Core\Bootstrap\LogCleanup
 * @internal
 */
class LogCleanupTaskService implements LogCleanupTaskServiceInterface
{
    /**
     * @return int
     */
    public function findLatestExecutionTimestamp() : int
    {
        $task = $this->getQueueService()->findLatestByType(LogCleanupTask::getClassName());
        if (!$task) {
            return 0;
        }
        return $task->getQueueTimestamp();
    }
    /**
     * @return void
     *
     * @throws QueueStorageUnavailableException
     */
    public function enqueueLogCleanupTask() : void
    {
        $this->getQueueService()->enqueue('log-cleanup', new LogCleanupTask());
    }
    protected function getQueueService() : QueueService
    {
        return ServiceRegister::getService(QueueService::class);
    }
}
