<?php

namespace CAWL\OnlinePayments\Core\Bootstrap\DataAccess\Monitoring;

use DateInterval;
use DateTime;
use Exception;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\Monitoring\MonitoringLog as MonitoringLogEntity;
use CAWL\OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\LogSettingsRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\MonitoringLog;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\MonitoringLogRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\RepositoryWithAdvancedSearchInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
/**
 * Class MonitoringLogRepository
 *
 * @package OnlinePayments\Core\Bootstrap\DataAccess\Monitoring
 * @internal
 */
class MonitoringLogRepository implements MonitoringLogRepositoryInterface
{
    private RepositoryWithAdvancedSearchInterface $repository;
    private StoreContext $storeContext;
    private ActiveConnectionProvider $activeConnectionProvider;
    private ActiveBrandProviderInterface $activeBrandProvider;
    private LogSettingsRepositoryInterface $logSettingsRepository;
    /**
     * @param RepositoryWithAdvancedSearchInterface $repository
     * @param StoreContext $storeContext
     * @param ActiveConnectionProvider $activeConnectionProvider
     * @param ActiveBrandProviderInterface $activeBrandProvider
     * @param LogSettingsRepositoryInterface $logSettingsRepository
     */
    public function __construct(RepositoryWithAdvancedSearchInterface $repository, StoreContext $storeContext, ActiveConnectionProvider $activeConnectionProvider, ActiveBrandProviderInterface $activeBrandProvider, LogSettingsRepositoryInterface $logSettingsRepository)
    {
        $this->repository = $repository;
        $this->storeContext = $storeContext;
        $this->activeConnectionProvider = $activeConnectionProvider;
        $this->activeBrandProvider = $activeBrandProvider;
        $this->logSettingsRepository = $logSettingsRepository;
    }
    /**
     * @param MonitoringLog $monitoringLog
     *
     * @return void
     *
     * @throws Exception
     */
    public function saveMonitoringLog(MonitoringLog $monitoringLog) : void
    {
        $brand = $this->activeBrandProvider->getActiveBrand();
        $mode = \str_contains($monitoringLog->getRequestEndpoint(), $brand->getLiveApiEndpoint()) !== \false ? ConnectionMode::live() : ConnectionMode::test();
        $activeConnection = $this->activeConnectionProvider->get();
        if ($activeConnection !== null) {
            $mode = $activeConnection->getMode();
        }
        $logSettings = $this->logSettingsRepository->getLogSettings();
        $expiresAt = $monitoringLog->getCreatedAt()->add(new DateInterval('P14D'));
        if ($logSettings) {
            $expiresAt = $monitoringLog->getCreatedAt()->add(new DateInterval('P' . $logSettings->getLogRecordsLifetime()->getDays() . 'D'));
        }
        $monitoringLog->setOrderLink($this->getOrderUrl($monitoringLog));
        $entity = new MonitoringLogEntity();
        $entity->setStoreId($this->storeContext->getStoreId());
        $entity->setMode((string) $mode);
        $entity->setOrderId($monitoringLog->getOrderId());
        $entity->setPaymentNumber($monitoringLog->getPaymentNumber());
        $entity->setCreatedAt($monitoringLog->getCreatedAt()->getTimestamp());
        $entity->setExpiresAt($expiresAt->getTimestamp());
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
    public function getLogs(int $pageNumber, int $pageSize, string $searchTerm) : array
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
    public function getAllLogs() : array
    {
        $activeConnection = $this->activeConnectionProvider->get();
        if (null === $activeConnection) {
            return [];
        }
        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())->where('mode', Operators::EQUALS, (string) $activeConnection->getMode());
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
    public function count(?DateTime $disconnectTime = null) : int
    {
        $activeConnection = $this->activeConnectionProvider->get();
        if (null === $activeConnection) {
            return 0;
        }
        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, $this->storeContext->getStoreId())->where('mode', Operators::EQUALS, (string) $activeConnection->getMode());
        if ($disconnectTime) {
            $queryFilter->where('createdAt', Operators::LESS_THAN, $disconnectTime->getTimestamp());
        }
        return $this->repository->count($queryFilter);
    }
    /**
     * @param DateTime $beforeDate
     * @param string $mode
     * @param int $limit
     *
     * @return void
     *
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
     * @param MonitoringLog $monitoringLog
     *
     * @return string
     */
    public function getOrderUrl(MonitoringLog $monitoringLog) : string
    {
        return '';
    }
}
