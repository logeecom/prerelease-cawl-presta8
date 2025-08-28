<?php

namespace OnlinePayments\Core\Tests\Infrastructure\Configuration;

use OnlinePayments\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use OnlinePayments\Core\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;

/**
 * Class ConfigurationTest.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\logger
 */
class ConfigurationTest extends BaseInfrastructureTestWithServices
{
    /**
     * Tests storing and retrieving value from config service
     *
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testStoringValue()
    {
        $this->shopConfig->saveMinLogLevel(5);
        $this->assertEquals(5, $this->shopConfig->getMinLogLevel());
        $this->shopConfig->saveMinLogLevel(2);
        $this->assertEquals(2, $this->shopConfig->getMinLogLevel());

        $this->shopConfig->setDefaultLoggerEnabled(false);
        $this->assertFalse($this->shopConfig->isDefaultLoggerEnabled());
        $this->shopConfig->setDefaultLoggerEnabled(true);
        $this->assertTrue($this->shopConfig->isDefaultLoggerEnabled());

        $this->shopConfig->setDebugModeEnabled(false);
        $this->assertFalse($this->shopConfig->isDebugModeEnabled());
        $this->shopConfig->setDebugModeEnabled(true);
        $this->assertTrue($this->shopConfig->isDebugModeEnabled());
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testGetDebugModeEnabled()
    {
        // arrange
        $this->shopConfig->setDebugModeEnabled(true);

        // act
        $status = $this->shopConfig->isDebugModeEnabled();

        // assert
        self::assertTrue($status);
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testGetDebugModeEnabledStatusNotSet()
    {
        // act
        $status = $this->shopConfig->isDebugModeEnabled();

        // assert
        self::assertFalse($status);
    }
}
