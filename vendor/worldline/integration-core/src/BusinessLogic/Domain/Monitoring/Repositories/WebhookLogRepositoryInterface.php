<?php

namespace OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories;

use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\WebhookLog;

/**
 * Interface WebhookLogRepositoryInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories
 */
interface WebhookLogRepositoryInterface
{
    /**
     * @param WebhookLog $webhookLog
     *
     * @return void
     */
    public function saveWebhookLog(WebhookLog $webhookLog): void;

    /**
     * @param int $pageNumber
     * @param int $pageSize
     * @param string $searchTerm
     *
     * @return WebhookLog[]
     */
    public function getWebhookLogs(int $pageNumber, int $pageSize, string $searchTerm): array;

    /**
     * @return WebhookLog[]
     */
    public function getAllLogs(): array;

    /**
     * @param \DateTime|null $disconnectTime
     *
     * @return int
     */
    public function count(?\DateTime $disconnectTime = null): int;

    /**
     * @param string $mode
     * @param int $limit
     *
     * @return void
     */
    public function deleteByMode(string $mode, int $limit): void;

    /**
     * @param WebhookLog $webhookLog
     *
     * @return string
     */
    public function getOrderUrl(WebhookLog $webhookLog): string;
}
