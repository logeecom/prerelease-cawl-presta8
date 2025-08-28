<?php

namespace OnlinePayments\Core\Tests\Infrastructure\ORM\Entity;

use OnlinePayments\Core\Infrastructure\TaskExecution\QueueItem;

/**
 * Class QueueItemTest.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\ORM\Entity
 */
class QueueItemTest extends GenericEntityTest
{
    /**
     * Returns entity full class name
     *
     * @return string
     */
    public function getEntityClass(): string
    {
        return QueueItem::getClassName();
    }
}
