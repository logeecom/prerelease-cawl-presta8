<?php

namespace OnlinePayments\Classes\Repositories;

use Exception;
use OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use OnlinePayments\Core\Infrastructure\Logger\Logger;
use OnlinePayments\Core\Infrastructure\ORM\Interfaces\ConditionallyDeletes;
use OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use OnlinePayments\Core\Infrastructure\ORM\Utility\IndexHelper;
use OnlinePayments\Core\Infrastructure\ServiceRegister;

/**
 * Class BaseRepositoryWithConditionalDelete
 *
 * @package OnlinePayments\Classes\Repositories
 */
class BaseRepositoryWithConditionalDelete extends BaseRepository implements ConditionallyDeletes
{
    /**
     * Fully qualified name of this class.
     */
    public const THIS_CLASS_NAME = __CLASS__;
    /**
     * @inheritDoc
     */
    public function deleteWhere(QueryFilter $queryFilter = null): void
    {
        try {
            $entity = new $this->entityClass;
            $type = $entity->getConfig()->getType();
            $indexMap = IndexHelper::mapFieldsToIndexes($entity);

            $groups = $queryFilter ? $this->buildConditionGroups($queryFilter, $indexMap) : [];
            $queryParts = $this->getQueryParts($groups, $indexMap);

            $whereClause = $this->generateWhereStatement($queryParts);

            \Db::getInstance()->delete($this->getDbName(), $whereClause . " AND entity_type='" . pSQL($type) . "'");
        } catch (Exception $e) {
            Logger::logError('Delete where failed with error ' . $e->getMessage());
        }
    }

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
}
