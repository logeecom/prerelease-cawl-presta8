<?php

namespace OnlinePayments\Core\Infrastructure\TaskExecution\TaskEvents\Listeners;

use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Infrastructure\TaskExecution\Composite\Orchestrator;
use OnlinePayments\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use OnlinePayments\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use OnlinePayments\Core\Infrastructure\TaskExecution\QueueItem;
use OnlinePayments\Core\Infrastructure\TaskExecution\QueueService;
use RuntimeException;

/**
 * Class OnReportProgress
 *
 * @package OnlinePayments\Core\Infrastructure\TaskExecution\TaskEvents\Listeners
 */
class OnReportProgress
{
    /**
     * Handles queue item progress change.
     *
     * @param QueueItem $queueItem
     * @param $progressBasePoints
     *
     * @throws QueueStorageUnavailableException
     * @throws QueueItemDeserializationException
     */
    public static function handle(QueueItem $queueItem, $progressBasePoints)
    {
        $queue = static::getQueueService();
        $queue->updateProgress($queueItem, $progressBasePoints);
        if ($queueItem->getParentId() === null) {
            return;
        }

        $parent = $queue->find($queueItem->getParentId());

        if ($parent === null) {
            throw new RuntimeException("Parent not available.");
        }

        /** @var Orchestrator $task */
        $task = $parent->getTask();
        if ($task === null || !($task instanceof Orchestrator)) {
            throw new RuntimeException("Failed to retrieve task.");
        }

        $task->updateSubJobProgress($queueItem->getId(), $queueItem->getProgressFormatted());
    }

    /**
     * Provides queue service.
     *
     * @return QueueService
     */
    private static function getQueueService(): QueueService
    {
        return ServiceRegister::getService(QueueService::CLASS_NAME);
    }
}
