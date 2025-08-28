<?php

namespace OnlinePayments\Core\Tests\Bootstrap;

use OnlinePayments\Core\Infrastructure\Configuration\Configuration;
use OnlinePayments\Core\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use OnlinePayments\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use OnlinePayments\Core\Infrastructure\Serializer\Concrete\JsonSerializer;
use OnlinePayments\Core\Infrastructure\Serializer\Serializer;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Infrastructure\Utility\TimeProvider;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Logger\TestDefaultLogger;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Logger\TestShopLogger;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TestShopConfiguration;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class BaseTestCase.
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\Common
 */
abstract class BaseTestCase extends TestCase
{
    public TestShopConfiguration $shopConfig;
    public TestShopLogger $shopLogger;

    public TestTimeProvider $timeProvider;
    public TestDefaultLogger $defaultLogger;
    private JsonSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        TestBootstrapComponent::bootstrap(static function () {
            return 'WOP';
        });

        $this->shopConfig = ServiceRegister::getService(Configuration::class);
        $this->shopLogger = ServiceRegister::getService(ShopLoggerAdapter::class);
        $this->defaultLogger = ServiceRegister::getService(DefaultLoggerAdapter::class);
        $this->serializer = ServiceRegister::getService(Serializer::class);
        $this->timeProvider = ServiceRegister::getService(TimeProvider::class);
    }

    protected function tearDown(): void
    {
        TestBootstrapComponent::reset();

        parent::tearDown();
    }
}