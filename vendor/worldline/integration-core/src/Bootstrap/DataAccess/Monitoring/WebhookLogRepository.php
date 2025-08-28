<?php

namespace OnlinePayments\Core\Bootstrap\DataAccess\Monitoring;

use DateTime;
use OnlinePayments\Core\Bootstrap\DataAccess\Monitoring\WebhookLog as WebhookLogEntity;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\RepositoryWithAdvancedSearchInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\WebhookLogRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\WebhookLog;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;

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

    /**
     * @param RepositoryWithAdvancedSearchInterface $repository
     * @param StoreContext $storeContext
     * @param ActiveConnectionProvider $activeConnectionProvider
     */
    public function __construct(
        RepositoryWithAdvancedSearchInterface $repository,
        StoreContext $storeContext,
        ActiveConnectionProvider $activeConnectionProvider
    ) {
        $this->repository = $repository;
        $this->storeContext = $storeContext;
        $this->activeConnectionProvider = $activeConnectionProvider;
    }

    /**
     * @inheritDoc
     */
    public function saveWebhookLog(WebhookLog $webhookLog): void
    {
        $activeConnection = $this->activeConnectionProvider->get();

        if ($activeConnection === null) {
            return;
        }

        $webhookLog->setOrderLink($this->getOrderUrl($webhookLog));
        $entity = new WebhookLogEntity();
        $entity->setStoreId($this->storeContext->getStoreId());
        $entity->setMode((string)$activeConnection->getMode());
        $entity->setOrderId($webhookLog->getOrderId());
        $entity->setPaymentNumber($webhookLog->getPaymentNumber());
        $entity->setCreatedAt($webhookLog->getCreatedAt()->getTimestamp());
        $entity->setWebhookLog($webhookLog);
        $this->repository->save($entity);
    }

    /**
     * @inheritDoc
     */
    public function getWebhookLogs(int $pageNumber, int $pageSize, string $searchTerm): array
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
    public function getAllLogs(): array
    {
        $activeConnection = $this->activeConnectionProvider->get();
        if (null === $activeConnection) {
            return [];
        }

        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())
            ->where('mode', Operators::EQUALS, (string)$activeConnection->getMode());

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
    public function count(?DateTime $disconnectTime = null): int
    {
        $activeConnection = $this->activeConnectionProvider->get();
        if (null === $activeConnection) {
            return 0;
        }

        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())
            ->where('mode', Operators::EQUALS, (string)$activeConnection->getMode());

        if ($disconnectTime) {
            $queryFilter->where('createdAt', Operators::GREATER_THAN, $disconnectTime->getTimestamp());
        }

        return $this->repository->count($queryFilter);
    }

    /**
     * @inheritDoc
     * @throws QueryFilterInvalidParamException
     */
    public function deleteByMode(string $mode, int $limit): void
    {
        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())
            ->where('mode', Operators::EQUALS, $mode)
            ->setLimit($limit);

        $this->repository->deleteWhere($queryFilter);
    }

    /**
     * @param WebhookLog $webhookLog
     *
     * @return string
     */
    public function getOrderUrl(WebhookLog $webhookLog): string
    {
        return '';
    }
}
