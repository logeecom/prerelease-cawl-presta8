<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories;

use DateTime;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\MonitoringLog;
/**
 * Interface MonitoringLogRepositoryInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories
 * @internal
 */
interface MonitoringLogRepositoryInterface
{
    /**
     * @param MonitoringLog $monitoringLog
     *
     * @return void
     */
    public function saveMonitoringLog(MonitoringLog $monitoringLog) : void;
    /**
     * @param int $pageNumber
     * @param int $pageSize
     * @param string $searchTerm
     *
     * @return MonitoringLog[]
     */
    public function getLogs(int $pageNumber, int $pageSize, string $searchTerm) : array;
    /**
     * @return MonitoringLog[]
     */
    public function getAllLogs() : array;
    /**
     * @param DateTime|null $disconnectTime
     *
     * @return int
     */
    public function count(?DateTime $disconnectTime = null) : int;
    /**
     * @param DateTime $beforeDate
     * @param string $mode
     * @param int $limit
     *
     * @return void
     */
    public function deleteByMode(DateTime $beforeDate, string $mode, int $limit) : void;
    /**
     * @return int
     */
    public function countExpired() : int;
    /**
     * @param int $limit
     *
     * @return void
     */
    public function deleteExpired(int $limit = 5000) : void;
    /**
     * @param MonitoringLog $monitoringLog
     *
     * @return string
     */
    public function getOrderUrl(MonitoringLog $monitoringLog) : string;
}
