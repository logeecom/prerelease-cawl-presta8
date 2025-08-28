<?php

namespace OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories;

use OnlinePayments\Core\Bootstrap\DataAccess\Monitoring\MonitoringLog;
use OnlinePayments\Core\Bootstrap\DataAccess\Monitoring\WebhookLog;

/**
 * Interface RepositoryWithAdvancedSearchInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories
 */
interface RepositoryWithAdvancedSearchInterface
{
    /**
     * @param int $pageNumber
     * @param int $pageSize
     * @param string $searchTerm
     *
     * @return WebhookLog[]|MonitoringLog[]
     */
    public function getLogs(int $pageNumber, int $pageSize, string $searchTerm): array;
}
