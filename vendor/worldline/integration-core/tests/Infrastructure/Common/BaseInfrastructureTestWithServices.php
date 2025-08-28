<?php
/** @noinspection PhpDuplicateArrayKeysInspection */

namespace OnlinePayments\Core\Tests\Infrastructure\Common;

use DateTime;
use Exception;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Tests\Bootstrap\TestBootstrapComponent;
use PHPUnit\Framework\TestCase;
use OnlinePayments\Core\Infrastructure\Configuration\ConfigEntity;
use OnlinePayments\Core\Infrastructure\Configuration\Configuration;
use OnlinePayments\Core\Infrastructure\Configuration\ConfigurationManager;
use OnlinePayments\Core\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use OnlinePayments\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use OnlinePayments\Core\Infrastructure\Logger\Logger;
use OnlinePayments\Core\Infrastructure\Logger\LoggerConfiguration;
use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use OnlinePayments\Core\Infrastructure\Serializer\Concrete\JsonSerializer;
use OnlinePayments\Core\Infrastructure\Serializer\Serializer;
use OnlinePayments\Core\Infrastructure\Utility\Events\EventBus;
use OnlinePayments\Core\Infrastructure\Utility\TimeProvider;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Logger\TestDefaultLogger;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Logger\TestShopLogger;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TestConfigurationManager;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TestShopConfiguration;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;

/**
 * Class BaseTest.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\Common
 */
abstract class BaseInfrastructureTestWithServices extends TestCase
{
    public TestShopConfiguration $shopConfig;
    public TestShopLogger $shopLogger;

    public TestTimeProvider $timeProvider;
    public TestDefaultLogger $defaultLogger;
    private JsonSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        TestBootstrapComponent::init();

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
