<?php

namespace CAWL\OnlinePayments\Classes\Repositories;

use DateTime;
use CAWL\OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\RepositoryWithAdvancedSearchInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Entity;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Utility\IndexHelper;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
/**
 * Class MonitoringLogsRepository
 *
 * @package OnlinePayments\Classes\Repositories
 */
class MonitoringLogsRepository extends BaseRepositoryWithConditionalDelete implements RepositoryWithAdvancedSearchInterface
{
    /**
     * Fully qualified name of this class.
     */
    public const THIS_CLASS_NAME = __CLASS__;
    public const TABLE_NAME = 'monitoring_logs';
    /**
     * Retrieves db_name for DBAL.
     *
     * @return string
     */
    protected function getDbName() : string
    {
        /** @var ActiveBrandProviderInterface $provider */
        $provider = ServiceRegister::getService(ActiveBrandProviderInterface::class);
        return \strtolower($provider->getActiveBrand()->getCode()) . '_' . self::TABLE_NAME;
    }
    public function getLogs(int $pageNumber, int $pageSize, string $searchTerm, ?DateTime $disconnectTime = null) : array
    {
        /** @var Entity $entity */
        $entity = new $this->entityClass();
        $cartId = \Cart::getCartIdByOrderId(pSQL($searchTerm));
        $queryFilter = $this->getLogsQuery($disconnectTime);
        $queryFilter->setOffset(($pageNumber - 1) * $pageSize)->setLimit($pageSize);
        $fieldIndexMap = IndexHelper::mapFieldsToIndexes($entity);
        $groups = $this->buildConditionGroups($queryFilter, $fieldIndexMap);
        $type = $entity->getConfig()->getType();
        $searchCondition = $this->getSearchCondition($searchTerm, $cartId);
        $typeCondition = "entity_type='" . pSQL($type) . "'";
        $whereCondition = $this->buildWhereCondition($groups, $fieldIndexMap);
        $result = $this->getRecordsByCondition($typeCondition . ' AND ' . $whereCondition . $searchCondition, $queryFilter);
        return $this->unserializeEntities($result);
    }
    public function countLogs(?DateTime $disconnectTime = null, string $searchTerm = '') : ?int
    {
        /** @var Entity $entity */
        $entity = new $this->entityClass();
        $cartId = \Cart::getCartIdByOrderId(pSQL($searchTerm));
        $queryFilter = $this->getLogsQuery($disconnectTime);
        $fieldIndexMap = IndexHelper::mapFieldsToIndexes($entity);
        $groups = $this->buildConditionGroups($queryFilter, $fieldIndexMap);
        $type = $entity->getConfig()->getType();
        $typeCondition = "entity_type='" . pSQL($type) . "'";
        $whereCondition = $this->buildWhereCondition($groups, $fieldIndexMap);
        $searchCondition = $this->getSearchCondition($searchTerm, $cartId);
        $result = $this->getRecordsByCondition($typeCondition . ' AND ' . $whereCondition . $searchCondition, $queryFilter);
        return \count($result);
    }
    /**
     * @param DateTime|null $disconnectTime
     *
     * @return QueryFilter
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getLogsQuery(?DateTime $disconnectTime) : QueryFilter
    {
        /** @var ActiveConnectionProvider $activeConnectionProvider */
        $activeConnectionProvider = ServiceRegister::getService(ActiveConnectionProvider::class);
        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, StoreContext::getInstance()->getStoreId())->where('mode', Operators::EQUALS, (string) $activeConnectionProvider->get()->getMode())->orderBy('createdAt', 'DESC');
        if ($disconnectTime) {
            $queryFilter->where('createdAt', Operators::GREATER_THAN, $disconnectTime->getTimestamp());
        }
        return $queryFilter;
    }
    /**
     * @param string $searchTerm
     * @param $cartId
     * @return string
     */
    public function getSearchCondition(string $searchTerm, $cartId) : string
    {
        $searchCondition = 'AND (index_3 LIKE \'%' . pSQL($searchTerm) . '%\' OR';
        if ($cartId) {
            $searchCondition .= ' index_4 LIKE \'%' . pSQL($cartId) . '%\' OR';
        }
        $searchCondition .= ' index_5 LIKE \'%' . pSQL($searchTerm) . '%\')';
        return $searchCondition;
    }
}
