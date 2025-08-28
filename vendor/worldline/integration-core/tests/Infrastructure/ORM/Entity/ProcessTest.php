<?php

namespace OnlinePayments\Core\Tests\Infrastructure\ORM\Entity;

use OnlinePayments\Core\Infrastructure\TaskExecution\Process;

/**
 * Class ProcessTest.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\ORM\Entity
 */
class ProcessTest extends GenericEntityTest
{
    /**
     * Returns entity full class name
     *
     * @return string
     */
    public function getEntityClass(): string
    {
        return Process::getClassName();
    }
}
