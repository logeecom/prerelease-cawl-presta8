<?php

namespace OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM;

use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;

/**
 * Class TestRepositoryRegistry.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM
 */
class TestRepositoryRegistry extends RepositoryRegistry
{
    /**
     * @return void
     */
    public static function cleanUp()
    {
        static::$repositories = [];
        static::$instantiated = [];
    }
}
