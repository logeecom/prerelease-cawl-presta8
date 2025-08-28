<?php

namespace OnlinePayments\Tests\Repositories;

use OnlinePayments\Classes\Repositories\QueueItemRepository;

/**
 * Class TestQueueItemRepository
 *
 * @package OnlinePayments\Tests\Repositories
 */
class TestQueueItemRepository extends QueueItemRepository
{
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;
    /**
     * Name of the base entity table in database.
     */
    const TABLE_NAME = 'op_test';

    /**
     * Retrieves db_name for DBAL.
     *
     * @return string
     */
    protected function getDbName(): string
    {
        return self::TABLE_NAME;
    }
}
