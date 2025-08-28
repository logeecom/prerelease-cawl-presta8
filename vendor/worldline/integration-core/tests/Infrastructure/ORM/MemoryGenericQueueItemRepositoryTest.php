<?php

namespace OnlinePayments\Core\Tests\Infrastructure\ORM;

use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;

/**
 * Class MemoryGenericQueueItemRepositoryTest.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\ORM
 */
class MemoryGenericQueueItemRepositoryTest extends AbstractGenericQueueItemRepositoryTest
{
    /**
     * @return string
     */
    public function getQueueItemEntityRepositoryClass(): string
    {
        return MemoryQueueItemRepository::getClassName();
    }

    /**
     * Cleans up all storage Services used by repositories
     */
    public function cleanUpStorage()
    {
        MemoryStorage::reset();
    }
}
