<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\DataAccess\Monitoring;

use DateInterval;
use DateTime;
use Exception;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\Monitoring\WebhookLog as WebhookLogEntity;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\LogSettingsRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\RepositoryWithAdvancedSearchInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\WebhookLogRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\WebhookLog;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
/**
 * Class WebhookLogRepository
 *
 * @package OnlinePayments\Core\Bootstrap\DataAccess\Monitoring
 */
class WebhookLogRepository implements WebhookLogRepositoryInterface
{
    private RepositoryWithAdvancedSearchInterface $repository;
    private StoreContext $storeContext;
    private ActiveConnectionProvider $activeConnectionProvider;
    private LogSettingsRepositoryInterface $logSettingsRepository;
    /**
     * @param RepositoryWithAdvancedSearchInterface $repository
     * @param StoreContext $storeContext
     * @param ActiveConnectionProvider $activeConnectionProvider
     * @param LogSettingsRepositoryInterface $logSettingsRepository
     */
    public function __construct(RepositoryWithAdvancedSearchInterface $repository, StoreContext $storeContext, ActiveConnectionProvider $activeConnectionProvider, LogSettingsRepositoryInterface $logSettingsRepository)
    {
        $this->repository = $repository;
        $this->storeContext = $storeContext;
        $this->activeConnectionProvider = $activeConnectionProvider;
        $this->logSettingsRepository = $logSettingsRepository;
    }
    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function saveWebhookLog(WebhookLog $webhookLog) : void
    {
        $activeConnection = $this->activeConnectionProvider->get();
        if ($activeConnection === null) {
            return;
        }
        $logSettings = $this->logSettingsRepository->getLogSettings();
        $createdAtDate = clone $webhookLog->getCreatedAt();
        $createdAt = $webhookLog->getCreatedAt()->getTimestamp();
        $expiresAt = $webhookLog->getCreatedAt()->add(new DateInterval('P14D'))->getTimestamp();
        if ($logSettings) {
            $expiresAt = $createdAtDate->add(new DateInterval('P' . $logSettings->getLogRecordsLifetime()->getDays() . 'D'))->getTimestamp();
        }
        $webhookLog->setOrderLink($this->getOrderUrl($webhookLog));
        $entity = new WebhookLogEntity();
        $entity->setStoreId($this->storeContext->getStoreId());
        $entity->setMode((string) $activeConnection->getMode());
        $entity->setOrderId($webhookLog->getOrderId());
        $entity->setPaymentNumber($webhookLog->getPaymentNumber());
        $entity->setCreatedAt($createdAt);
        $entity->setExpiresAt($expiresAt);
        $entity->setWebhookLog($webhookLog);
        $this->repository->save($entity);
    }
    /**
     * @inheritDoc
     */
    public function getWebhookLogs(int $pageNumber, int $pageSize, string $searchTerm) : array
    {
        $activeConnection = $this->activeConnectionProvider->get();
        if (null === $activeConnection) {
            return [];
        }
        /** @var WebhookLogEntity[] $entities */
        $entities = $this->repository->getLogs($pageNumber, $pageSize, $searchTerm);
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $entity->getWebhookLog();
        }
        return $result;
    }
    /**
     * @return WebhookLog[]
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getAllLogs() : array
    {
        $activeConnection = $this->activeConnectionProvider->get();
        if (null === $activeConnection) {
            return [];
        }
        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())->where('mode', Operators::EQUALS, (string) $activeConnection->getMode());
        /** @var WebhookLogEntity[] $entities */
        $entities = $this->repository->select($queryFilter);
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $entity->getWebhookLog();
        }
        return $result;
    }
    /**
     * @param DateTime|null $disconnectTime
     *
     * @return int
     *
     * @throws QueryFilterInvalidParamException
     */
    public function count(?DateTime $disconnectTime = null) : int
    {
        $activeConnection = $this->activeConnectionProvider->get();
        if (null === $activeConnection) {
            return 0;
        }
        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())->where('mode', Operators::EQUALS, (string) $activeConnection->getMode());
        if ($disconnectTime) {
            $queryFilter->where('createdAt', Operators::GREATER_THAN, $disconnectTime->getTimestamp());
        }
        return $this->repository->count($queryFilter);
    }
    /**
     * @inheritDoc
     * @throws QueryFilterInvalidParamException
     */
    public function deleteByMode(DateTime $beforeDate, string $mode, int $limit) : void
    {
        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())->where('mode', Operators::EQUALS, $mode)->where('createdAt', Operators::LESS_THAN, $beforeDate->getTimestamp())->setLimit($limit);
        $this->repository->deleteWhere($queryFilter);
    }
    /**
     * @return int
     *
     * @throws QueryFilterInvalidParamException
     */
    public function countExpired() : int
    {
        $queryFilter = new QueryFilter();
        $queryFilter->where('expiresAt', Operators::LESS_THAN, (new DateTime())->getTimestamp());
        return $this->repository->count($queryFilter);
    }
    /**
     * @param int $limit
     *
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function deleteExpired(int $limit = 5000) : void
    {
        $queryFilter = new QueryFilter();
        $queryFilter->where('expiresAt', Operators::LESS_THAN, (new DateTime())->getTimestamp())->setLimit($limit);
        $this->repository->deleteWhere($queryFilter);
    }
    /**
     * @param WebhookLog $webhookLog
     *
     * @return string
     */
    public function getOrderUrl(WebhookLog $webhookLog) : string
    {
        return '';
    }
}
