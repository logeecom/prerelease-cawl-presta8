<?php

namespace CAWL\OnlinePayments\Classes;

use CAWL\OnlinePayments\Classes\Repositories\BaseRepository;
use CAWL\OnlinePayments\Classes\Repositories\BaseRepositoryWithConditionalDelete;
use CAWL\OnlinePayments\Classes\Repositories\MonitoringLogsRepository;
use CAWL\OnlinePayments\Classes\Repositories\PaymentTransactionLocksRepository;
use CAWL\OnlinePayments\Classes\Repositories\PaymentTransactionsRepository;
use CAWL\OnlinePayments\Classes\Repositories\ProductTypesRepository;
use CAWL\OnlinePayments\Classes\Repositories\QueueItemRepository;
use CAWL\OnlinePayments\Classes\Repositories\TokensRepository;
use CAWL\OnlinePayments\Classes\Repositories\WebhookLogsRepository;
use CAWL\OnlinePayments\Classes\Services\Checkout\CartProviderService;
use CAWL\OnlinePayments\Classes\Services\Checkout\PaymentOptionsService;
use CAWL\OnlinePayments\Classes\Repositories\ConfigurationRepository;
use CAWL\OnlinePayments\Classes\Services\Domain\Repositories\MonitoringLogRepository;
use CAWL\OnlinePayments\Classes\Services\Domain\Repositories\PaymentTransactionRepository;
use CAWL\OnlinePayments\Classes\Services\Domain\Repositories\WebhookLogRepository;
use CAWL\OnlinePayments\Classes\Services\Integration\ConfigService;
use CAWL\OnlinePayments\Classes\Services\Integration\Logger\LoggerService;
use CAWL\OnlinePayments\Classes\Services\Integration\MetadataProvider;
use CAWL\OnlinePayments\Classes\Services\Integration\VersionInfoService;
use CAWL\OnlinePayments\Core\Bootstrap\BootstrapComponent;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\Connection\ConnectionConfigEntity;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\PayByLinkSettingsEntity;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\PaymentSettingsConfigEntity;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\Disconnect\DisconnectTime;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\LogSettingsEntity;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\Monitoring\MonitoringLog;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\Monitoring\WebhookLog;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\PaymentLink\PaymentLinkEntity;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PaymentTransactionEntity;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PaymentTransactionLockEntity;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\ProductTypes\ProductTypeEntity;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\Tokens\TokenEntity;
use CAWL\OnlinePayments\Core\Bootstrap\SingleInstance;
use CAWL\OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\LogSettingsRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Payment\PaymentService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Checkout\Cart\CartProvider;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\PaymentMethod\PaymentMethodConfigEntity;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Encryption\Encryptor;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Language\LanguageService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Logo\LogoUrlService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Metadata\MetadataProviderInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\ShopOrderService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Stores\StoreService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Version\VersionService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\MonitoringLogRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\WebhookLogRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentTransactionRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Repositories\PaymentConfigRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Time\TimeProviderInterface;
use CAWL\OnlinePayments\Core\Infrastructure\Configuration\ConfigEntity;
use CAWL\OnlinePayments\Core\Infrastructure\Configuration\Configuration;
use CAWL\OnlinePayments\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use CAWL\OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use CAWL\OnlinePayments\Core\Infrastructure\Serializer\Concrete\JsonSerializer;
use CAWL\OnlinePayments\Core\Infrastructure\Serializer\Serializer;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Process;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\QueueItem;
/**
 * Class Bootstrap
 *
 * @package OnlinePayments\Classes
 */
class Bootstrap extends BootstrapComponent
{
    public static function boot(string $moduleName, string $brandCode) : void
    {
        ServiceRegister::registerService(\Module::class, function () use($moduleName) {
            return \Module::getInstanceByName($moduleName);
        });
        static::bootstrap(function () use($brandCode) {
            return $brandCode;
        }, __DIR__ . '/brand.json');
    }
    protected static function initServices() : void
    {
        parent::initServices();
        ServiceRegister::registerService(Serializer::class, function () {
            return new JsonSerializer();
        });
        ServiceRegister::registerService(Configuration::class, function () {
            return ConfigService::getInstance();
        });
        ServiceRegister::registerService(ShopLoggerAdapter::class, function () {
            return new LoggerService(ServiceRegister::getService(\Module::class));
        });
        ServiceRegister::registerService(StoreService::class, function () {
            return new Services\Integration\StoreService(new ConfigurationRepository(), ServiceRegister::getService(\Module::class));
        });
        ServiceRegister::registerService(VersionService::class, function () {
            return new VersionInfoService();
        });
        ServiceRegister::registerService(PaymentOptionsService::class, function () {
            return new PaymentOptionsService(ServiceRegister::getService(\Module::class), \Context::getContext(), ServiceRegister::getService(CartProvider::class));
        });
        ServiceRegister::registerService(CartProvider::class, static function () {
            return new CartProviderService(\Context::getContext());
        });
        ServiceRegister::registerService(Encryptor::class, function () {
            return new Services\Integration\Encryptor();
        });
        ServiceRegister::registerService(ShopPaymentService::class, function () {
            return new Services\Integration\ShopPaymentService();
        });
        ServiceRegister::registerService(ShopOrderService::class, function () {
            return new Services\Integration\ShopOrderService(ServiceRegister::getService(\Module::class));
        });
        ServiceRegister::registerService(\CAWL\OnlinePayments\Core\BusinessLogic\Domain\Stores\StoreService::class, function () {
            return new Services\Domain\StoreService(ServiceRegister::getService(StoreService::class), ServiceRegister::getService(ConnectionConfigRepositoryInterface::class));
        });
        ServiceRegister::registerService(LanguageService::class, function () {
            return new Services\Integration\LanguageService();
        });
        ServiceRegister::registerService(MetadataProviderInterface::class, function () {
            return new MetadataProvider(ServiceRegister::getService(\Module::class));
        });
        ServiceRegister::registerService(LogoUrlService::class, function () {
            return new Services\Integration\LogoUrlService();
        });
        ServiceRegister::registerService(PaymentProductService::class, function () {
            return new Services\Integration\PaymentProductService();
        });
    }
    protected static function initRepositories() : void
    {
        parent::initRepositories();
        ServiceRegister::registerService(PaymentTransactionRepositoryInterface::class, new SingleInstance(static function () {
            return new PaymentTransactionRepository(RepositoryRegistry::getRepository(PaymentTransactionEntity::class), RepositoryRegistry::getRepository(PaymentTransactionLockEntity::class), StoreContext::getInstance(), ServiceRegister::getService(TimeProviderInterface::class));
        }));
        ServiceRegister::registerService(MonitoringLogRepositoryInterface::class, new SingleInstance(static function () {
            return new MonitoringLogRepository(RepositoryRegistry::getRepository(MonitoringLog::class), StoreContext::getInstance(), ServiceRegister::getService(ActiveConnectionProvider::class), ServiceRegister::getService(ActiveBrandProviderInterface::class), ServiceRegister::getService(LogSettingsRepositoryInterface::class));
        }));
        ServiceRegister::registerService(WebhookLogRepositoryInterface::class, new SingleInstance(static function () {
            return new WebhookLogRepository(RepositoryRegistry::getRepository(WebhookLog::class), StoreContext::getInstance(), ServiceRegister::getService(ActiveConnectionProvider::class), ServiceRegister::getService(LogSettingsRepositoryInterface::class));
        }));
        RepositoryRegistry::registerRepository(Process::class, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(ConfigEntity::class, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(ConnectionConfigEntity::class, BaseRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(PaymentMethodConfigEntity::class, BaseRepositoryWithConditionalDelete::getClassName());
        RepositoryRegistry::registerRepository(PaymentTransactionEntity::class, PaymentTransactionsRepository::getClassName());
        RepositoryRegistry::registerRepository(PaymentTransactionLockEntity::class, PaymentTransactionLocksRepository::getClassName());
        RepositoryRegistry::registerRepository(TokenEntity::class, TokensRepository::getClassName());
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
