<?php

namespace OnlinePayments\Core\Tests\Infrastructure\ORM;

use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;

/**
 * Class MemoryGenericStudentRepositoryTest.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\ORM
 */
class MemoryGenericStudentRepositoryTest extends AbstractGenericStudentRepositoryTest
{
    /**
     * @return string
     */
    public function getStudentEntityRepositoryClass(): string
    {
        return MemoryRepository::getClassName();
    }

    /**
     * Cleans up all storage Services used by repositories
     */
    public function cleanUpStorage()
    {
        MemoryStorage::reset();
    }
}
