<?php

namespace OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Monitoring;

use Exception;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Disconnect\Repositories\DisconnectRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\MonitoringLog;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\MonitoringLogRepositoryInterface;

/**
 * Class MonitoringLogsService
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Monitoring
 */
class MonitoringLogsService
{
    protected MonitoringLogRepositoryInterface $monitoringLogRepository;
    protected DisconnectRepositoryInterface $repository;

    /**
     * @param MonitoringLogRepositoryInterface $monitoringLogRepository
     * @param DisconnectRepositoryInterface $repository
     */
    public function __construct(MonitoringLogRepositoryInterface $monitoringLogRepository, DisconnectRepositoryInterface $repository)
    {
        $this->monitoringLogRepository = $monitoringLogRepository;
        $this->repository = $repository;
    }

    /**
     * @param int $pageNumber
     * @param int $pageSize
     * @param string $searchTerm
     *
     * @return MonitoringLog[]
     */
    public function getLogs(int $pageNumber, int $pageSize, string $searchTerm): array
    {
        return $this->monitoringLogRepository->getLogs($pageNumber, $pageSize, $searchTerm);
    }

    /**
     * @return array
     */
    public function getAllLogs(): array
    {
        $logs = $this->monitoringLogRepository->getAllLogs();
        $result = [];

        foreach ($logs as $log) {
            $result[] = $log->toArray();
        }

        return $result;
    }

    /**
     * @return int
     *
     * @throws Exception
     */
    public function count(): int
    {
        $disconnectTime = $this->repository->getDisconnectTime();

        return $this->monitoringLogRepository->count($disconnectTime);
    }
}
