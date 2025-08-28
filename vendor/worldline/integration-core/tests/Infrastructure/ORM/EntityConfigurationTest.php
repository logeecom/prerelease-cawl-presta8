<?php

namespace OnlinePayments\Core\Tests\Infrastructure\ORM;

use OnlinePayments\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use OnlinePayments\Core\Infrastructure\ORM\Configuration\IndexMap;
use PHPUnit\Framework\TestCase;

/**
 * Class EntityConfigurationTest
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\ORM
 */
class EntityConfigurationTest extends TestCase
{
    /**
     * @return void
     */
    public function testEntityConfiguration()
    {
        $map = new IndexMap();
        $type = 'test';
        $config = new EntityConfiguration($map, $type);

        $this->assertEquals($map, $config->getIndexMap());
        $this->assertEquals($type, $config->getType());
    }
}
