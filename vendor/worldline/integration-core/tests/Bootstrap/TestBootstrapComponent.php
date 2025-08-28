<?php

namespace OnlinePayments\Core\Tests\Bootstrap;

use DateTime;
use OnlinePayments\Core\Bootstrap\BootstrapComponent;
use OnlinePayments\Core\Bootstrap\DataAccess\Connection\ConnectionConfigEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\Disconnect\DisconnectTime;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\CardsSettingsEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\LogSettingsEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\PayByLinkSettingsEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\PaymentSettingsConfigEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentMethod\PaymentMethodConfigEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentMethod\PaymentMethodConfigRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PaymentTransactionEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\ProductTypes\ProductTypeEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\Tokens\TokenEntity;
use OnlinePayments\Core\Bootstrap\SingleInstance;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection\Proxies\ConnectionProxyInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Encryption\Encryptor;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Metadata\MetadataProviderInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\ShopOrderService;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Version\VersionService;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Repositories\PaymentConfigRepositoryInterface;
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
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use OnlinePayments\Core\Infrastructure\TaskExecution\Process;
use OnlinePayments\Core\Infrastructure\TaskExecution\QueueItem;
use OnlinePayments\Core\Infrastructure\Utility\TimeProvider;
use OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Connection\Mocks\MockConnectionProxyInterface;
use OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Connection\Mocks\MockEncryptor;
use OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Payment\Mocks\MockShopPaymentService;
use OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Versions\Mocks\MockVersionService;
use OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks\MockMetadataProvider;
use OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks\MockShopOrderService;
use OnlinePayments\Core\Tests\BusinessLogic\Common\MemoryRepositoryWithConditionalDelete;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Logger\TestDefaultLogger;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Logger\TestShopLogger;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TestConfigurationManager;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\TestShopConfiguration;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;

class TestBootstrapComponent extends BootstrapComponent
{
    public static function init(): void
    {
        parent::init();

        ServiceRegister::registerService(ConfigurationManager::class, function () {
            return new TestConfigurationManager();
        });
        ServiceRegister::registerService(Configuration::class, new SingleInstance(function () {
            return new TestShopConfiguration();
        }));
        ServiceRegister::registerService(TimeProvider::class, new SingleInstance(function () {
            $timeProvider = new TestTimeProvider();
            $timeProvider->setCurrentLocalTime(new DateTime());

            return $timeProvider;
        }));
        ServiceRegister::registerService(DefaultLoggerAdapter::class, new SingleInstance(function () {
            return new TestDefaultLogger();
        }));
        ServiceRegister::registerService(ShopLoggerAdapter::class, new SingleInstance(function () {
            return new TestShopLogger();
        }));
        ServiceRegister::registerService(Serializer::class, new SingleInstance(function () {
            return new JsonSerializer();
        }));
        ServiceRegister::registerService(TaskRunnerWakeup::class, function () {
            return new TestTaskRunnerWakeupService();
        });
        ServiceRegister::registerService(ConnectionProxyInterface::class, function () {
            return new MockConnectionProxyInterface();
        });
        ServiceRegister::registerService(
            VersionService::class,
            function () {
                return new MockVersionService();
            }
        );

        ServiceRegister::registerService(
            Encryptor::class,
            function () {
                return new MockEncryptor();
            }
        );

        ServiceRegister::registerService(
            ShopOrderService::class,
            function () {
                return new MockShopOrderService();
            }
        );

        ServiceRegister::registerService(
            PaymentConfigRepositoryInterface::class,
            function () {
                return new PaymentMethodConfigRepository(
                    RepositoryRegistry::getRepository(PaymentMethodConfigEntity::class),
                    StoreContext::getInstance(),
                    ServiceRegister::getService(ActiveConnectionProvider::class)
                );
            }
        );

        ServiceRegister::registerService(
            ShopPaymentService::class,
            function () {
                return new MockShopPaymentService();
            }
        );

        ServiceRegister::registerService(MetadataProviderInterface::class, function () {
            return new MockMetadataProvider();
        });
    }

    protected static function initRepositories(): void
    {
        parent::initRepositories();

        RepositoryRegistry::registerRepository(ConfigEntity::class, MemoryRepository::getClassName());
        RepositoryRegistry::registerRepository(PaymentMethodConfigEntity::class, MemoryRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(ConnectionConfigEntity::class, MemoryRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(PaymentTransactionEntity::class, MemoryRepository::getClassName());
        RepositoryRegistry::registerRepository(TokenEntity::class, MemoryRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(Process::class, MemoryRepository::getClassName());
        RepositoryRegistry::registerRepository(CardsSettingsEntity::class, MemoryRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(PaymentSettingsConfigEntity::class, MemoryRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(LogSettingsEntity::class, MemoryRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(DisconnectTime::class, MemoryRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(QueueItem::class, MemoryQueueItemRepository::getClassName());
        RepositoryRegistry::registerRepository(PayByLinkSettingsEntity::class, MemoryRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(ProductTypeEntity::class, MemoryRepositoryWithConditionalDelete::getClassName());
    }

    public static function reset(): void
    {
        TestRepositoryRegistry::cleanUp();
        MemoryStorage::reset();
        Logger::resetInstance();
        LoggerConfiguration::resetInstance();
    }
}