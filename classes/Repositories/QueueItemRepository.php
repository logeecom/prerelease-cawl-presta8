<?php

namespace CAWL\OnlinePayments\Classes\Repositories;

use Exception;
use CAWL\OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use CAWL\OnlinePayments\Core\Infrastructure\Logger\Logger;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Entity;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Interfaces\QueueItemRepository as CoreQueueItemRepository;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\Operators;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\Utility\IndexHelper;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\QueueItem;
/**
 * Class QueueItemRepository
 *
 * @package OnlinePayments\Classes\Repositories
 * @internal
 */
class QueueItemRepository extends BaseRepository implements CoreQueueItemRepository
{
    /**
     * Fully qualified name of this class.
     */
    public const THIS_CLASS_NAME = __CLASS__;
    public const TABLE_NAME = 'queue';
    /**
     * Removes data from the database that matches the specified query filters.
     *
     * @param QueryFilter|null $queryFilter
     *
     * @return void
     */
    public function deleteWhere(QueryFilter $queryFilter = null) : void
    {
        try {
            $entity = new $this->entityClass();
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
     * @inheritDoc
     */
    public function findOldestQueuedItems(int $priority, int $limit = 10) : array
    {
        $queuedItems = [];
        try {
            $runningQueueNames = $this->getRunningQueueNames();
            $queuedItems = $this->getQueuedItems($priority, $runningQueueNames, $limit);
        } catch (\PrestaShopDatabaseException $exception) {
            // In case of database exception return empty result set.
        }
        return $queuedItems;
    }
    /**
     * @inheritDoc
     */
    public function saveWithCondition(QueueItem $queueItem, array $additionalWhere = []) : int
    {
        if ($queueItem->getId()) {
            $this->updateQueueItem($queueItem, $additionalWhere);
            return $queueItem->getId();
        }
        return $this->save($queueItem);
    }
    /**
     * @inheritDoc
     */
    public function batchStatusUpdate(array $ids, string $status)
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . $this->getDbName() . '` SET `status` = "' . pSQL($status) . '" WHERE `id` IN (' . \implode(',', \array_map('intval', $ids)) . ')';
        \Db::getInstance()->execute($sql);
    }
    /**
     * Updates queue item.
     *
     * @param QueueItem $queueItem
     * @param array $additionalWhere
     *
     * @return void
     *
     * @throws QueueItemSaveException
     * @throws QueryFilterInvalidParamException
     * @throws \PrestaShopDatabaseException
     */
    protected function updateQueueItem(QueueItem $queueItem, array $additionalWhere) : void
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $queueItem->getId());
        foreach ($additionalWhere as $name => $value) {
            $filter->where($name, Operators::EQUALS, $value ?? '');
        }
        /** @var QueueItem $item */
        $item = $this->selectOne($filter);
        if ($item === null) {
            throw new QueueItemSaveException("Cannot update queue item with id {$queueItem->getId()}.");
        }
        $this->update($queueItem);
    }
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
    /**
     * Returns names of queues containing items that are currently in progress.
     *
     * @return array
     *
     * @throws QueryFilterInvalidParamException
     * @throws \PrestaShopDatabaseException
     */
    protected function getRunningQueueNames() : array
    {
        $filter = new QueryFilter();
        $filter->where('status', Operators::EQUALS, pSQL(QueueItem::IN_PROGRESS));
        $filter->setLimit(10000);
        /** @var QueueItem[] $runningQueueItems */
        $runningQueueItems = $this->select($filter);
        return \array_map(function (QueueItem $runningQueueItem) {
            return $runningQueueItem->getQueueName();
        }, $runningQueueItems);
    }
    /**
     * Returns all queued items.
     *
     * @param int $priority
     * @param array $runningQueueNames
     * @param int $limit
     *
     * @return Entity[]
     *
     * @throws \PrestaShopException
     */
    protected function getQueuedItems(int $priority, array $runningQueueNames, int $limit) : array
    {
        $queuedItems = [];
        $queueNameIndex = $this->getIndexMapping('queueName');
        try {
            $condition = \sprintf(' %s', $this->buildWhereString(['entity_type' => 'QueueItem', $this->getIndexMapping('status') => QueueItem::QUEUED, $this->getIndexMapping('priority') => $priority]));
            if (!empty($runningQueueNames)) {
                $condition .= \sprintf(' AND ' . $queueNameIndex . " NOT IN ('%s')", \implode("', '", \array_map('pSQL', $runningQueueNames)));
            }
            $queueNamesQuery = new \DbQuery();
            $queueNamesQuery->select($queueNameIndex . ', MIN(id) AS id')->from($this->getDbName())->where($condition)->groupBy($queueNameIndex)->limit($limit);
            $query = 'SELECT queueTable.id,queueTable.data' . ' FROM (' . $queueNamesQuery->build() . ') AS queueView' . ' INNER JOIN ' . bqSQL(_DB_PREFIX_ . $this->getDbName()) . ' AS queueTable' . ' ON queueView.id = queueTable.id';
            $records = \Db::getInstance()->executeS($query);
            $queuedItems = $this->unserializeEntities($records);
        } catch (\PrestaShopDatabaseException $exception) {
            // In case of exception return empty result set
        }
        return $queuedItems;
    }
    /**
     * Build properly escaped where condition string based on given key/value parameters.
     * String parameters will be sanitized with pSQL method call and other fields will be cast to integer values
     *
     * @param array $whereFields Key value pairs of where condition
     *
     * @return string Properly sanitized where condition string
     */
    protected function buildWhereString(array $whereFields = []) : string
    {
        $where = [];
        foreach ($whereFields as $field => $value) {
            $where[] = \is_int($value) ? bqSQL($field) . Operators::EQUALS . pSQL($value) : bqSQL($field) . Operators::EQUALS . "'" . pSQL($value) . "'";
        }
        return \implode(' AND ', $where);
    }
}
