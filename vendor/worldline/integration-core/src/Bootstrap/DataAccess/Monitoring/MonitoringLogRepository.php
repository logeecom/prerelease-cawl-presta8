<?php

namespace OnlinePayments\Core\Bootstrap\DataAccess\Monitoring;

use DateTime;
use OnlinePayments\Core\Bootstrap\DataAccess\Monitoring\MonitoringLog as MonitoringLogEntity;
use OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\MonitoringLog;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\MonitoringLogRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\RepositoryWithAdvancedSearchInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;

/**
 * Class MonitoringLogRepository
 *
 * @package OnlinePayments\Core\Bootstrap\DataAccess\Monitoring
 */
class MonitoringLogRepository implements MonitoringLogRepositoryInterface
{
    private RepositoryWithAdvancedSearchInterface $repository;
    private StoreContext $storeContext;
    private ActiveConnectionProvider $activeConnectionProvider;
    private ActiveBrandProviderInterface $activeBrandProvider;

    /**
     * @param RepositoryWithAdvancedSearchInterface $repository
     * @param StoreContext $storeContext
     * @param ActiveConnectionProvider $activeConnectionProvider
     * @param ActiveBrandProviderInterface $activeBrandProvider
     */
    public function __construct(
        RepositoryWithAdvancedSearchInterface $repository,
        StoreContext $storeContext,
        ActiveConnectionProvider $activeConnectionProvider,
        ActiveBrandProviderInterface $activeBrandProvider
    ) {
        $this->repository = $repository;
        $this->storeContext = $storeContext;
        $this->activeConnectionProvider = $activeConnectionProvider;
        $this->activeBrandProvider = $activeBrandProvider;
    }

    /**
     * @param MonitoringLog $monitoringLog
     *
     * @return void
     */
    public function saveMonitoringLog(MonitoringLog $monitoringLog): void
    {
        $brand = $this->activeBrandProvider->getActiveBrand();
        $mode = str_contains($monitoringLog->getRequestEndpoint(), $brand->getLiveApiEndpoint()) !== false
            ? ConnectionMode::live() : ConnectionMode::test();
        $activeConnection = $this->activeConnectionProvider->get();

        if ($activeConnection !== null) {
            $mode = $activeConnection->getMode();
        }

        $monitoringLog->setOrderLink($this->getOrderUrl($monitoringLog));

        $entity = new MonitoringLogEntity();
        $entity->setStoreId($this->storeContext->getStoreId());
        $entity->setMode((string)$mode);
        $entity->setOrderId($monitoringLog->getOrderId());
        $entity->setPaymentNumber($monitoringLog->getPaymentNumber());
        $entity->setCreatedAt($monitoringLog->getCreatedAt()->getTimestamp());
        $entity->setMessage($monitoringLog->getMessage());
        $entity->setMonitoringLog($monitoringLog);
        $this->repository->save($entity);
    }

    /**
     * @param int $pageNumber
     * @param int $pageSize
     * @param string $searchTerm
     *
     * @return array
     */
    public function getLogs(int $pageNumber, int $pageSize, string $searchTerm): array
    {
        $activeConnection = $this->activeConnectionProvider->get();
        if (null === $activeConnection) {
            return [];
        }

        /** @var MonitoringLogEntity[] $entities */
        $entities = $this->repository->getLogs($pageNumber, $pageSize, $searchTerm);
        $result = [];

        foreach ($entities as $entity) {
            $result[] = $entity->getMonitoringLog();
        }

        return $result;
    }

    /**
     * @return array
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

        /** @var MonitoringLogEntity[] $entities */
        $entities = $this->repository->select($queryFilter);
        $result = [];

        foreach ($entities as $entity) {
            $result[] = $entity->getMonitoringLog();
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
     * @param string $mode
     * @param int $limit
     *
     * @return void
     *
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
     * @param MonitoringLog $monitoringLog
     *
     * @return string
     */
    public function getOrderUrl(MonitoringLog $monitoringLog): string
    {
        return '';
    }
}
