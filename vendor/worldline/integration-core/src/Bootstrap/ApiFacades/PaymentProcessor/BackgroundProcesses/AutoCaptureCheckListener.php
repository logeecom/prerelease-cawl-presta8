<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Time\TimeProviderInterface;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\QueueService;
/**
 * Class AutoCaptureCheckListener.
 *
 * @package OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses
 * @internal
 */
class AutoCaptureCheckListener
{
    private QueueService $queueService;
    private TimeProviderInterface $timeProvider;
    public function __construct(QueueService $queueService, TimeProviderInterface $timeProvider)
    {
        $this->queueService = $queueService;
        $this->timeProvider = $timeProvider;
    }
    public function handle() : void
    {
        if (!$this->canHandle()) {
            return;
        }
        $this->doHandle();
    }
    protected function canHandle() : bool
    {
        $task = $this->queueService->findLatestByType(AutoCaptureCheckTask::getClassName());
        $fifteenMinutesBeforeNow = $this->timeProvider->getCurrentLocalTime()->sub(new \DateInterval('PT15M'));
        return !$task || $task->getQueueTimestamp() < $fifteenMinutesBeforeNow->getTimestamp();
    }
    protected function doHandle() : void
    {
        $this->queueService->enqueue('auto_capture_check', new AutoCaptureCheckTask());
    }
}
