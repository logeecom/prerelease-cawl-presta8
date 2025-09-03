<?php

namespace OnlinePayments\Classes;

use OnlinePayments\Classes\Repositories\BaseRepository;
use OnlinePayments\Classes\Repositories\BaseRepositoryWithConditionalDelete;
use OnlinePayments\Classes\Repositories\MonitoringLogsRepository;
use OnlinePayments\Classes\Repositories\PaymentTransactionsRepository;
use OnlinePayments\Classes\Repositories\ProductTypesRepository;
use OnlinePayments\Classes\Repositories\QueueItemRepository;
use OnlinePayments\Classes\Repositories\TokensRepository;
use OnlinePayments\Classes\Repositories\WebhookLogsRepository;
use OnlinePayments\Classes\Services\Checkout\CartProviderService;
use OnlinePayments\Classes\Services\Checkout\PaymentOptionsService;
use OnlinePayments\Classes\Repositories\ConfigurationRepository;
use OnlinePayments\Classes\Services\Domain\Repositories\MonitoringLogRepository;
use OnlinePayments\Classes\Services\Domain\Repositories\PaymentTransactionRepository;
use OnlinePayments\Classes\Services\Domain\Repositories\WebhookLogRepository;
use OnlinePayments\Classes\Services\Integration\ConfigService;
use OnlinePayments\Classes\Services\Integration\Logger\LoggerService;
use OnlinePayments\Classes\Services\Integration\MetadataProvider;
use OnlinePayments\Classes\Services\Integration\VersionInfoService;
use OnlinePayments\Core\Bootstrap\BootstrapComponent;
use OnlinePayments\Core\Bootstrap\DataAccess\Connection\ConnectionConfigEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\CardsSettingsEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\PayByLinkSettingsEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\PaymentSettingsConfigEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\Disconnect\DisconnectTime;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\LogSettingsEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\Monitoring\MonitoringLog;
use OnlinePayments\Core\Bootstrap\DataAccess\Monitoring\WebhookLog;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentLink\PaymentLinkEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PaymentTransactionEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\ProductTypes\ProductTypeEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\Tokens\TokenEntity;
use OnlinePayments\Core\Bootstrap\SingleInstance;
use OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\LogSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Payment\PaymentService;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentMethod\PaymentMethodConfigEntity;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Encryption\Encryptor;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Language\LanguageService;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Logo\LogoUrlService;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Metadata\MetadataProviderInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\ShopOrderService;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Stores\StoreService;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Version\VersionService;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\MonitoringLogRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\WebhookLogRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentTransactionRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Repositories\PaymentConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Time\TimeProviderInterface;
use OnlinePayments\Core\Infrastructure\Configuration\ConfigEntity;
use OnlinePayments\Core\Infrastructure\Configuration\Configuration;
use OnlinePayments\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use OnlinePayments\Core\Infrastructure\Serializer\Concrete\JsonSerializer;
use OnlinePayments\Core\Infrastructure\Serializer\Serializer;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Infrastructure\TaskExecution\Process;
use OnlinePayments\Core\Infrastructure\TaskExecution\QueueItem;

/**
 * Class Bootstrap
 *
 * @package OnlinePayments\Classes
 */
class Bootstrap extends BootstrapComponent
{

    public static function boot(string $moduleName, string $brandCode): void
    {
        ServiceRegister::registerService(
            \Module::class,
            function () use ($moduleName) {
                return \Module::getInstanceByName($moduleName);
            }
        );

        static::bootstrap(function () use ($brandCode) {
            return $brandCode;
        });
    }

    protected static function initServices(): void
    {
        parent::initServices();

        ServiceRegister::registerService(
            Serializer::class,
            function () {
                return new JsonSerializer();
            }
        );

        ServiceRegister::registerService(
            Configuration::class,
            function () {
                return ConfigService::getInstance();
            }
        );

        ServiceRegister::registerService(
            ShopLoggerAdapter::class,
            function () {
                return new LoggerService();
            }
        );

        ServiceRegister::registerService(
            StoreService::class,
            function () {
                return new Services\Integration\StoreService(
                    new ConfigurationRepository()
                );
            }
        );

        ServiceRegister::registerService(
            VersionService::class,
            function () {
                return new VersionInfoService();
            }
        );

        ServiceRegister::registerService(
            PaymentOptionsService::class,
            function () {
                return new PaymentOptionsService(
                    ServiceRegister::getService(\Module::class),
                    \Context::getContext(),
                    ServiceRegister::getService(CartProvider::class),
                );
            }
        );

        ServiceRegister::registerService(CartProvider::class, static function () {
            return new CartProviderService(
                \Context::getContext()
            );
        });

        ServiceRegister::registerService(
            Encryptor::class,
            function () {
                return new Services\Integration\Encryptor();
            }
        );

        ServiceRegister::registerService(
            ShopPaymentService::class,
            function () {
                return new Services\Integration\ShopPaymentService();
            }
        );

        ServiceRegister::registerService(
            ShopOrderService::class,
            function () {
                return new Services\Integration\ShopOrderService(
                    ServiceRegister::getService(\Module::class),
                );
            }
        );

        ServiceRegister::registerService(
            \OnlinePayments\Core\BusinessLogic\Domain\Stores\StoreService::class,
            function () {
                return new Services\Domain\StoreService(
                    ServiceRegister::getService(StoreService::class),
                    ServiceRegister::getService(ConnectionConfigRepositoryInterface::class)
                );
            }
        );

        ServiceRegister::registerService(LanguageService::class,
            function () {
                return new Services\Integration\LanguageService();
            }
        );

        ServiceRegister::registerService(MetadataProviderInterface::class,
            function () {
                return new MetadataProvider(ServiceRegister::getService(\Module::class));
            }
        );

        ServiceRegister::registerService(PaymentService::class,
            function () {
                return new Services\Integration\PaymentService(
                    ServiceRegister::getService(PaymentConfigRepositoryInterface::class),
                    ServiceRegister::getService(LogoUrlService::class),
                    ServiceRegister::getService(ActiveBrandProviderInterface::class)
                );
            }
        );

        ServiceRegister::registerService(
            LogoUrlService::class,
            function () {
                return new Services\Integration\LogoUrlService();
            }
        );
    }

    protected static function initRepositories(): void
    {
        parent::initRepositories();

        ServiceRegister::registerService(PaymentTransactionRepositoryInterface::class, new SingleInstance(static function () {
            return new PaymentTransactionRepository(
                RepositoryRegistry::getRepository(PaymentTransactionEntity::class),
                StoreContext::getInstance(),
                ServiceRegister::getService(TimeProviderInterface::class)
            );
        }));

        ServiceRegister::registerService(MonitoringLogRepositoryInterface::class, new SingleInstance(static function () {
            return new MonitoringLogRepository(
                RepositoryRegistry::getRepository(MonitoringLog::class),
                StoreContext::getInstance(),
                ServiceRegister::getService(ActiveConnectionProvider::class),
                ServiceRegister::getService(ActiveBrandProviderInterface::class),
                ServiceRegister::getService(LogSettingsRepositoryInterface::class)
            );
        }));

        ServiceRegister::registerService(WebhookLogRepositoryInterface::class, new SingleInstance(static function () {
            return new WebhookLogRepository(
                RepositoryRegistry::getRepository(WebhookLog::class),
                StoreContext::getInstance(),
                ServiceRegister::getService(ActiveConnectionProvider::class),
                ServiceRegister::getService(LogSettingsRepositoryInterface::class)
            );
        }));

        RepositoryRegistry::registerRepository(Process::class, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(ConfigEntity::class, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(ConnectionConfigEntity::class, BaseRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(PaymentMethodConfigEntity::class, BaseRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(PaymentTransactionEntity::class, PaymentTransactionsRepository::getClassName());
        RepositoryRegistry::registerRepository(TokenEntity::class, TokensRepository::getClassName());
        RepositoryRegistry::registerRepository(CardsSettingsEntity::class, BaseRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(PaymentSettingsConfigEntity::class, BaseRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(LogSettingsEntity::class, BaseRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(DisconnectTime::class, BaseRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(QueueItem::class, QueueItemRepository::getClassName());
        RepositoryRegistry::registerRepository(PayByLinkSettingsEntity::class, BaseRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(ProductTypeEntity::class, ProductTypesRepository::getClassName());
        RepositoryRegistry::registerRepository(MonitoringLog::class, MonitoringLogsRepository::getClassName());
        RepositoryRegistry::registerRepository(WebhookLog::class, WebhookLogsRepository::getClassName());
        RepositoryRegistry::registerRepository(PaymentLinkEntity::class, BaseRepositoryWithConditionalDelete::getClassName());
    }
}
