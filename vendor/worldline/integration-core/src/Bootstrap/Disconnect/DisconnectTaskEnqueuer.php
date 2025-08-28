<?php

namespace OnlinePayments\Core\Bootstrap\Disconnect;

use DateTime;
use Exception;
use OnlinePayments\Core\Bootstrap\Disconnect\Tasks\DisconnectTask;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Disconnect\Repositories\DisconnectRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Disconnect\DisconnectTaskEnqueuerInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use OnlinePayments\Core\Infrastructure\TaskExecution\QueueService;

/**
 * Class DisconnectTaskEnqueuer
 *
 * @package OnlinePayments\Core\Bootstrap\Disconnect
 */
class DisconnectTaskEnqueuer implements DisconnectTaskEnqueuerInterface
{
    protected DisconnectRepositoryInterface $disconnectRepository;
    protected QueueService $queueService;

    /**
     * @param DisconnectRepositoryInterface $disconnectRepository
     * @param QueueService $queueService
     */
    public function __construct(DisconnectRepositoryInterface $disconnectRepository, QueueService $queueService)
    {
        $this->disconnectRepository = $disconnectRepository;
        $this->queueService = $queueService;
    }

    /**
     * @inheritDoc
     *
     * @throws QueueStorageUnavailableException
     * @throws Exception
     */
    public function enqueueDisconnectTask(): void
    {
        $disconnectTime = new DateTime();
        $this->disconnectRepository->setDisconnectTime($disconnectTime);
        $this->queueService->enqueue(
            'disconnect-integration',
            new DisconnectTask(StoreContext::getInstance()->getStoreId(), $disconnectTime)
        );
    }
}
