<?php

namespace CAWL\OnlinePayments\Classes\Repositories;

use DateTime;
use CAWL\OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\RepositoryWithAdvancedSearchInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Entity;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Utility\IndexHelper;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
/**
 * Class WebhookLogsRepository
 *
 * @package OnlinePayments\Classes\Repositories
 */
class WebhookLogsRepository extends BaseRepositoryWithConditionalDelete implements RepositoryWithAdvancedSearchInterface
{
    /**
     * Fully qualified name of this class.
     */
    public const THIS_CLASS_NAME = __CLASS__;
    public const TABLE_NAME = 'webhook_logs';
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
        $queryFilter = $this->getQuery($disconnectTime);
        $queryFilter->setOffset(($pageNumber - 1) * $pageSize)->setLimit($pageSize);
        $fieldIndexMap = IndexHelper::mapFieldsToIndexes($entity);
        $groups = $this->buildConditionGroups($queryFilter, $fieldIndexMap);
        $type = $entity->getConfig()->getType();
        $typeCondition = "entity_type='" . pSQL($type) . "'";
        $whereCondition = $this->buildWhereCondition($groups, $fieldIndexMap);
        $searchCondition = $this->getSearchCondition($cartId, $searchTerm);
        $result = $this->getRecordsByCondition($typeCondition . ' AND ' . $whereCondition . $searchCondition, $queryFilter);
        return $this->unserializeEntities($result);
    }
    public function countLogs(?DateTime $disconnectTime = null, string $searchTerm = '') : ?int
    {
        /** @var Entity $entity */
        $entity = new $this->entityClass();
        $cartId = \Cart::getCartIdByOrderId(pSQL($searchTerm));
        $queryFilter = $this->getQuery($disconnectTime);
        $fieldIndexMap = IndexHelper::mapFieldsToIndexes($entity);
        $groups = $this->buildConditionGroups($queryFilter, $fieldIndexMap);
        $type = $entity->getConfig()->getType();
        $typeCondition = "entity_type='" . pSQL($type) . "'";
        $whereCondition = $this->buildWhereCondition($groups, $fieldIndexMap);
        $searchCondition = $this->getSearchCondition($cartId, $searchTerm);
        $result = $this->getRecordsByCondition($typeCondition . ' AND ' . $whereCondition . $searchCondition, $queryFilter);
        return \count($result);
    }
    protected function getQuery(?DateTime $disconnectTime = null) : QueryFilter
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
     * @param $cartId
     * @param string $searchTerm
     * @return string
     */
    public function getSearchCondition($cartId, string $searchTerm) : string
    {
        $searchCondition = 'AND
             (
                index_3 LIKE \'%' . pSQL($cartId) . '%\' OR
                index_4 LIKE \'%' . pSQL($searchTerm) . '%\'
            )';
        if ($cartId === null) {
            $searchCondition = 'AND index_4 LIKE \'%' . pSQL($searchTerm) . '%\'';
        }
        return $searchCondition;
    }
}
