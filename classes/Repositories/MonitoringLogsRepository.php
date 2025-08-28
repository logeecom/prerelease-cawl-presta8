<?php

namespace OnlinePayments\Classes\Repositories;

use OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\RepositoryWithAdvancedSearchInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\Infrastructure\ORM\Entity;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use OnlinePayments\Core\Infrastructure\ORM\Utility\IndexHelper;
use OnlinePayments\Core\Infrastructure\ServiceRegister;

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
    protected function getDbName(): string
    {
        /** @var ActiveBrandProviderInterface $provider */
        $provider = ServiceRegister::getService(ActiveBrandProviderInterface::class);

        return strtolower($provider->getActiveBrand()->getCode()) . '_' . self::TABLE_NAME;
    }

    public function getLogs(int $pageNumber, int $pageSize, string $searchTerm): array
    {
        /** @var Entity $entity */
        $entity = new $this->entityClass;

        /** @var ActiveConnectionProvider $activeConnectionProvider */
        $activeConnectionProvider = ServiceRegister::getService(ActiveConnectionProvider::class);

        $queryFilter = new QueryFilter();
        $queryFilter->where('storeId', Operators::EQUALS, StoreContext::getInstance()->getStoreId())
            ->where('mode', Operators::EQUALS, (string)$activeConnectionProvider->get()->getMode())
            ->setOffset(($pageNumber - 1) * $pageSize)
            ->setLimit($pageSize)
            ->orderBy('createdAt', 'DESC');

        $fieldIndexMap = IndexHelper::mapFieldsToIndexes($entity);
        $groups = $this->buildConditionGroups($queryFilter, $fieldIndexMap);
        $type = $entity->getConfig()->getType();

        $typeCondition = "entity_type='" . pSQL($type) . "'";
        $whereCondition = $this->buildWhereCondition($groups, $fieldIndexMap);
        $result = $this->getRecordsByCondition(
            $typeCondition . ' AND ' . $whereCondition . 'AND
             (
                index_3 LIKE \'%' . pSQL($searchTerm) . '%\' OR
                index_4 LIKE \'%' . pSQL($searchTerm) . '%\' OR
                index_5 LIKE \'%' . pSQL($searchTerm) . '%\'
            )',
            $queryFilter
        );

        return $this->unserializeEntities($result);
    }
}
