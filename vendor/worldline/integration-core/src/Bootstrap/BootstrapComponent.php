<?php

namespace OnlinePayments\Core\Bootstrap;

use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\Proxies\ConnectionProxy;
use OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\CancelProxy;
use OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\CaptureProxy;
use OnlinePayments\Core\Bootstrap\ApiFacades\Order\Proxies\RefundProxy;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses\AutoCaptureCheckListener;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses\TransactionStatusCheckListener;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\BackgroundProcesses\WaitPaymentOutcomeProcessStarter;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\HostedCheckoutProxy;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\HostedTokenizationProxy;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\PaymentLinksProxy;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\PaymentMethodProxy;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\PaymentsProxy;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\Proxies\SurchargeProxy;
use OnlinePayments\Core\Bootstrap\DataAccess\Connection\ConnectionConfigEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\Connection\ConnectionConfigRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\Disconnect\DisconnectRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\Disconnect\DisconnectTime;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\CardsSettingsEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\CardsSettingsRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\LogSettingsEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\LogSettingsRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\PayByLinkSettingsEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\PayByLinkSettingsRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\PaymentSettingsConfigEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\GeneralSettings\PaymentSettingsRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\Maintenance\TaskCleanupRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentLink\PaymentLinkEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentLink\PaymentLinkRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentMethod\PaymentMethodConfigEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentMethod\PaymentMethodConfigRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\AuthorizedTransactionsRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PaymentTransactionEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PaymentTransactionRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PendingTransactionsRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\ProductTypes\ProductTypeEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\ProductTypes\ProductTypeRepository;
use OnlinePayments\Core\Bootstrap\DataAccess\Tokens\TokenEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\Tokens\TokensRepository;
use OnlinePayments\Core\Bootstrap\Disconnect\DisconnectTaskEnqueuer;
use OnlinePayments\Core\Bootstrap\Maintenance\TaskCleanupListener;
use OnlinePayments\Core\Bootstrap\Sdk\MerchantClientFactory;
use OnlinePayments\Core\Bootstrap\Sdk\WebhookTransformer;
use OnlinePayments\Core\Bootstrap\Time\TimeProvider;
use OnlinePayments\Core\Branding\Brand\ActiveBrandProvider;
use OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\ConnectionAPI\Controller\ConnectionController;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Controller\GeneralSettingsController;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\IntegrationAPI\Controller\IntegrationController;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\LanguageAPI\Controller\LanguageController;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\MonitoringAPI\Controller\LogsController;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Controller\PaymentController;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\ProductTypesAPI\Controller\ProductTypesController;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\StoreAPI\Controller\StoreController;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\VersionsAPI\Controller\VersionController;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection\ConnectionService;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection\Proxies\ConnectionProxyInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Disconnect\DisconnectService;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Disconnect\Repositories\DisconnectRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\GeneralSettingsService;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\CardsSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\LogSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\PayByLinkSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\PaymentSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Monitoring\MonitoringLogsService;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Monitoring\WebhookLogsService;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Payment\PaymentService;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\ProductTypes\ProductTypeService;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\ProductTypes\Repositories\ProductTypeRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Disconnect\DisconnectTaskEnqueuerInterface;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Repositories\TokensRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Encryption\Encryptor;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Language\LanguageService;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Logo\LogoUrlService;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Metadata\MetadataProviderInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\ShopOrderService;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Stores\StoreService as IntegrationStoreService;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Version\VersionService;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\MonitoringLogRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\Repositories\WebhookLogRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\BusinessLogic\Domain\OrderStatusMapping\StatusMappingService;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentTransactionRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\Repositories\PaymentLinkRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Repositories\PaymentConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Stores\StoreService;
use OnlinePayments\Core\BusinessLogic\Domain\Time\TimeProviderInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Webhook\Transformers\WebhookTransformerInterface;
use OnlinePayments\Core\BusinessLogic\Order\ApiFacades\CancelAPI\Controller\CancelController;
use OnlinePayments\Core\BusinessLogic\Order\ApiFacades\CaptureAPI\Controller\CaptureController;
use OnlinePayments\Core\BusinessLogic\Order\ApiFacades\OrdersAPI\Controller\OrderController;
use OnlinePayments\Core\BusinessLogic\Order\ApiFacades\RefundAPI\Controller\RefundController;
use OnlinePayments\Core\BusinessLogic\Order\Proxies\CancelProxyInterface;
use OnlinePayments\Core\BusinessLogic\Order\Proxies\CaptureProxyInterface;
use OnlinePayments\Core\BusinessLogic\Order\Proxies\RefundProxyInterface;
use OnlinePayments\Core\BusinessLogic\Order\Services\Cancel\CancelService;
use OnlinePayments\Core\BusinessLogic\Order\Services\Capture\CaptureService;
use OnlinePayments\Core\BusinessLogic\Order\Services\Order\OrderService;
use OnlinePayments\Core\BusinessLogic\Order\Services\Refund\RefundService;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\AdminAPI\Controller\PaymentLinksController;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Controller\HostedCheckoutController;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Controller\HostedTokenizationController;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Controller\PaymentController as CheckoutAPIPaymentController;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\CheckoutAPI\Controller\PaymentMethodsController;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\ApiFacades\WebhooksAPI\Controller\WebhooksController;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses\WaitPaymentOutcomeProcess;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\BackgroundProcesses\WaitPaymentOutcomeProcessStarterInterface;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\HostedCheckoutProxyInterface;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\HostedTokenizationProxyInterface;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\PaymentLinksProxyInterface;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\PaymentMethodProxyInterface;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\PaymentsProxyInterface;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\SurchargeProxyInterface;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Repositories\PaymentMethodConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\HostedCheckout\HostedCheckoutService;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\HostedTokenization\HostedTokenizationService;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\Payment\StatusUpdateService;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\PaymentLinks\PaymentLinksService;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\PaymentMethod\PaymentMethodService;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Services\Webhooks\WebhookService;
use OnlinePayments\Core\Infrastructure\BootstrapComponent as BaseBootstrapComponent;
use OnlinePayments\Core\Infrastructure\ORM\Interfaces\ConditionallyDeletes;
use OnlinePayments\Core\Infrastructure\ORM\Interfaces\QueueItemRepository;
use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use OnlinePayments\Core\Infrastructure\TaskExecution\QueueItem;
use OnlinePayments\Core\Infrastructure\TaskExecution\QueueService;
use OnlinePayments\Core\Infrastructure\TaskExecution\TaskEvents\TickEvent;
use OnlinePayments\Core\Infrastructure\Utility\Events\EventBus;
use OnlinePayments\Core\Infrastructure\Utility\TimeProvider as InfrastructureTimeProvider;

/**
 * Class BootstrapComponent
 *
 * @package OnlinePayments\Core\Bootstrap
 */
class BootstrapComponent extends BaseBootstrapComponent
{
    public static function bootstrap(callable $activeBrandResolver): void
    {
        static::init();

        ServiceRegister::registerService(
            ActiveBrandProviderInterface::class,
            new SingleInstance(static function () use ($activeBrandResolver) {
                return new ActiveBrandProvider($activeBrandResolver);
            })
        );
    }

    public static function init(): void
    {
        parent::init();

        static::initControllers();
        static::initProxies();
        static::initRequestProcessors();
        static::initBackgroundProcesses();
    }

    /**
     * @return void
     */
    protected static function initServices(): void
    {
        parent::initServices();

        ServiceRegister::registerService(TimeProviderInterface::class, new SingleInstance(static function () {
            return new TimeProvider(
                ServiceRegister::getService(InfrastructureTimeProvider::class),
            );
        }));

        ServiceRegister::registerService(PaymentMethodService::class, new SingleInstance(static function () {
            return new PaymentMethodService(
                ServiceRegister::getService(PaymentMethodConfigRepositoryInterface::class),
                ServiceRegister::getService(ProductTypeRepositoryInterface::class),
                ServiceRegister::getService(PaymentMethodProxyInterface::class),
                ServiceRegister::getService(SurchargeProxyInterface::class)
            );
        }));

        ServiceRegister::registerService(HostedTokenizationService::class, new SingleInstance(static function () {
            return new HostedTokenizationService(
                ServiceRegister::getService(HostedTokenizationProxyInterface::class),
                ServiceRegister::getService(PaymentsProxyInterface::class),
                ServiceRegister::getService(PaymentTransactionRepositoryInterface::class),
                ServiceRegister::getService(CardsSettingsRepositoryInterface::class),
                ServiceRegister::getService(PaymentSettingsRepositoryInterface::class),
                ServiceRegister::getService(TokensRepositoryInterface::class),
                ServiceRegister::getService(WaitPaymentOutcomeProcess::class),
                ServiceRegister::getService(LogoUrlService::class),
                ServiceRegister::getService(ActiveBrandProviderInterface::class)
            );
        }));

        ServiceRegister::registerService(HostedCheckoutService::class, new SingleInstance(static function () {
            return new HostedCheckoutService(
                ServiceRegister::getService(HostedCheckoutProxyInterface::class),
                ServiceRegister::getService(PaymentTransactionRepositoryInterface::class),
                ServiceRegister::getService(TokensRepositoryInterface::class),
                ServiceRegister::getService(CardsSettingsRepositoryInterface::class),
                ServiceRegister::getService(PaymentSettingsRepositoryInterface::class),
                ServiceRegister::getService(ProductTypeRepositoryInterface::class)
            );
        }));

        ServiceRegister::registerService(WaitPaymentOutcomeProcess::class, new SingleInstance(static function () {
            return new WaitPaymentOutcomeProcess(
                ServiceRegister::getService(PaymentTransactionRepositoryInterface::class),
                ServiceRegister::getService(StatusUpdateService::class),
                ServiceRegister::getService(TimeProviderInterface::class),
                ServiceRegister::getService(WaitPaymentOutcomeProcessStarterInterface::class)
            );
        }));

        ServiceRegister::registerService(StatusUpdateService::class, new SingleInstance(static function () {
            return new StatusUpdateService(
                ServiceRegister::getService(PaymentTransactionRepositoryInterface::class),
                ServiceRegister::getService(PaymentsProxyInterface::class),
                ServiceRegister::getService(TokensRepositoryInterface::class),
                ServiceRegister::getService(HostedTokenizationProxyInterface::class),
                ServiceRegister::getService(TimeProviderInterface::class),
                ServiceRegister::getService(ShopOrderService::class),
                ServiceRegister::getService(StatusMappingService::class),
                ServiceRegister::getService(PaymentSettingsRepositoryInterface::class)
            );
        }));

        ServiceRegister::registerService(ActiveConnectionProvider::class, new SingleInstance(static function () {
            return new ActiveConnectionProvider(
                ServiceRegister::getService(ConnectionConfigRepositoryInterface::class)
            );
        }));

        ServiceRegister::registerService(MerchantClientFactory::class, new SingleInstance(static function () {
            return new MerchantClientFactory(
                ServiceRegister::getService(ActiveConnectionProvider::class),
                ServiceRegister::getService(ActiveBrandProviderInterface::class),
                ServiceRegister::getService(MetadataProviderInterface::class),
            );
        }));

        ServiceRegister::registerService(ConnectionService::class, function () {
            return new ConnectionService(
                ServiceRegister::getService(ConnectionConfigRepositoryInterface::class),
                ServiceRegister::getService(ConnectionProxyInterface::class)
            );
        });

        ServiceRegister::registerService(PaymentService::class, function () {
            return new PaymentService(
                ServiceRegister::getService(PaymentConfigRepositoryInterface::class),
                ServiceRegister::getService(LogoUrlService::class),
                ServiceRegister::getService(ActiveBrandProviderInterface::class)
            );
        });

        ServiceRegister::registerService(
            StoreService::class,
            function () {
                return new StoreService(
                    ServiceRegister::getService(IntegrationStoreService::class),
                    ServiceRegister::getService(ConnectionConfigRepositoryInterface::class)
                );
            }
        );

        ServiceRegister::registerService(
            GeneralSettingsService::class,
            function () {
                return new GeneralSettingsService(
                    ServiceRegister::getService(ConnectionConfigRepositoryInterface::class),
                    ServiceRegister::getService(CardsSettingsRepositoryInterface::class),
                    ServiceRegister::getService(LogSettingsRepositoryInterface::class),
                    ServiceRegister::getService(PaymentSettingsRepositoryInterface::class),
                    ServiceRegister::getService(IntegrationStoreService::class),
                    ServiceRegister::getService(PayByLinkSettingsRepositoryInterface::class)
                );
            }
        );

        ServiceRegister::registerService(
            DisconnectService::class,
            function () {
                return new DisconnectService(
                    ServiceRegister::getService(ShopPaymentService::class),
                    ServiceRegister::getService(ConnectionConfigRepositoryInterface::class),
                    ServiceRegister::getService(CardsSettingsRepositoryInterface::class),
                    ServiceRegister::getService(PaymentSettingsRepositoryInterface::class),
                    ServiceRegister::getService(LogSettingsRepositoryInterface::class),
                    ServiceRegister::getService(PaymentMethodConfigRepositoryInterface::class),
                    ServiceRegister::getService(PayByLinkSettingsRepositoryInterface::class),
                    ServiceRegister::getService(DisconnectTaskEnqueuerInterface::class)
                );
            }
        );

        ServiceRegister::registerService(ProductTypeService::class, new SingleInstance(static function () {
            return new ProductTypeService(
                ServiceRegister::getService(ProductTypeRepositoryInterface::class)
            );
        }));

        ServiceRegister::registerService(WebhookService::class, new SingleInstance(static function () {
            return new WebhookService(
                ServiceRegister::getService(StatusUpdateService::class),
                ServiceRegister::getService(PaymentTransactionRepositoryInterface::class)
            );
        }));

        ServiceRegister::registerService(StatusMappingService::class, new SingleInstance(static function () {
            return new StatusMappingService(
                ServiceRegister::getService(GeneralSettingsService::class)
            );
        }));

        ServiceRegister::registerService(MonitoringLogsService::class, new SingleInstance(static function () {
            return new MonitoringLogsService(
                ServiceRegister::getService(MonitoringLogRepositoryInterface::class),
                ServiceRegister::getService(DisconnectRepositoryInterface::class)
            );
        }));

        ServiceRegister::registerService(WebhookLogsService::class, new SingleInstance(static function () {
            return new WebhookLogsService(
                ServiceRegister::getService(WebhookLogRepositoryInterface::class),
                ServiceRegister::getService(PaymentsProxyInterface::class),
                ServiceRegister::getService(DisconnectRepositoryInterface::class),
                ServiceRegister::getService(ActiveBrandProviderInterface::class)
            );
        }));

        ServiceRegister::registerService(PaymentLinksService::class, new SingleInstance(static function () {
            return new PaymentLinksService(
                ServiceRegister::getService(PaymentLinksProxyInterface::class),
                ServiceRegister::getService(CardsSettingsRepositoryInterface::class),
                ServiceRegister::getService(PaymentSettingsRepositoryInterface::class),
                ServiceRegister::getService(PayByLinkSettingsRepositoryInterface::class),
                ServiceRegister::getService(PaymentLinkRepositoryInterface::class),
                ServiceRegister::getService(PaymentTransactionRepositoryInterface::class),
            );
        }));

        ServiceRegister::registerService(DisconnectTaskEnqueuerInterface::class, new SingleInstance(static function () {
            return new DisconnectTaskEnqueuer(
                ServiceRegister::getService(DisconnectRepositoryInterface::class),
                ServiceRegister::getService(QueueService::class)
            );
        }));

        ServiceRegister::registerService(
            WebhookTransformerInterface::class,
            function () {
                return new WebhookTransformer(
                    ServiceRegister::getService(ActiveConnectionProvider::class)
                );
            }
        );

        ServiceRegister::registerService(OrderService::class, new SingleInstance(static function () {
            return new OrderService(
                ServiceRegister::getService(PaymentTransactionRepositoryInterface::class),
                ServiceRegister::getService(PaymentsProxyInterface::class)
            );
        }));

        ServiceRegister::registerService(CaptureService::class, new SingleInstance(static function () {
            return new CaptureService(
                ServiceRegister::getService(CaptureProxyInterface::class)
            );
        }));

        ServiceRegister::registerService(CancelService::class, new SingleInstance(static function () {
            return new CancelService(
                ServiceRegister::getService(CancelProxyInterface::class)
            );
        }));

        ServiceRegister::registerService(RefundService::class, new SingleInstance(static function () {
            return new RefundService(
                ServiceRegister::getService(RefundProxyInterface::class)
            );
        }));
    }

    /**
     * @return void
     */
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

        ServiceRegister::registerService(PendingTransactionsRepository::class, new SingleInstance(static function () {
            return new PendingTransactionsRepository(
                RepositoryRegistry::getRepository(PaymentTransactionEntity::class),
                ServiceRegister::getService(TimeProviderInterface::class)
            );
        }));

        ServiceRegister::registerService(AuthorizedTransactionsRepository::class, new SingleInstance(static function () {
            return new AuthorizedTransactionsRepository(
                RepositoryRegistry::getRepository(PaymentTransactionEntity::class),
                ServiceRegister::getService(TimeProviderInterface::class)
            );
        }));

        ServiceRegister::registerService(TokensRepositoryInterface::class, new SingleInstance(static function () {
            /** @var ConditionallyDeletes $repository */
            $repository = RepositoryRegistry::getRepository(TokenEntity::class);
            return new TokensRepository(
                $repository,
                StoreContext::getInstance(),
            );
        }));

        ServiceRegister::registerService(PaymentMethodConfigRepositoryInterface::class, new SingleInstance(static function () {
            return new PaymentMethodConfigRepository(
                RepositoryRegistry::getRepository(PaymentMethodConfigEntity::class),
                StoreContext::getInstance(),
                ServiceRegister::getService(ActiveConnectionProvider::class)
            );
        }));

        ServiceRegister::registerService(PaymentConfigRepositoryInterface::class, new SingleInstance(static function () {
            return new PaymentMethodConfigRepository(
                RepositoryRegistry::getRepository(PaymentMethodConfigEntity::class),
                StoreContext::getInstance(),
                ServiceRegister::getService(ActiveConnectionProvider::class)
            );
        }));

        ServiceRegister::registerService(ConnectionConfigRepositoryInterface::class, new SingleInstance(static function () {
            return new ConnectionConfigRepository(
                RepositoryRegistry::getRepository(ConnectionConfigEntity::class),
                StoreContext::getInstance(),
                ServiceRegister::getService(Encryptor::class)
            );
        }));

        ServiceRegister::registerService(CardsSettingsRepositoryInterface::class, new SingleInstance(static function () {
            return new CardsSettingsRepository(
                RepositoryRegistry::getRepository(CardsSettingsEntity::class),
                StoreContext::getInstance(),
                ServiceRegister::getService(ActiveConnectionProvider::class)
            );
        }));

        ServiceRegister::registerService(PaymentSettingsRepositoryInterface::class, new SingleInstance(static function () {
            return new PaymentSettingsRepository(
                RepositoryRegistry::getRepository(PaymentSettingsConfigEntity::class),
                StoreContext::getInstance(),
                ServiceRegister::getService(ActiveConnectionProvider::class)
            );
        }));

        ServiceRegister::registerService(LogSettingsRepositoryInterface::class, new SingleInstance(static function () {
            return new LogSettingsRepository(
                RepositoryRegistry::getRepository(LogSettingsEntity::class),
                StoreContext::getInstance(),
                ServiceRegister::getService(ActiveConnectionProvider::class)
            );
        }));

        ServiceRegister::registerService(DisconnectRepositoryInterface::class, new SingleInstance(static function () {
            return new DisconnectRepository(
                StoreContext::getInstance(),
                RepositoryRegistry::getRepository(DisconnectTime::class)
            );
        }));

        ServiceRegister::registerService(PayByLinkSettingsRepositoryInterface::class, new SingleInstance(static function () {
            return new PayByLinkSettingsRepository(
                RepositoryRegistry::getRepository(PayByLinkSettingsEntity::class),
                StoreContext::getInstance(),
                ServiceRegister::getService(ActiveConnectionProvider::class)
            );
        }));

        ServiceRegister::registerService(PaymentLinkRepositoryInterface::class, new SingleInstance(static function () {
            return new PaymentLinkRepository(
                RepositoryRegistry::getRepository(PaymentLinkEntity::class),
                StoreContext::getInstance(),
            );
        }));

        ServiceRegister::registerService(ProductTypeRepositoryInterface::class, new SingleInstance(static function () {
            /** @var ConditionallyDeletes $repository */
            $repository = RepositoryRegistry::getRepository(ProductTypeEntity::class);
            return new ProductTypeRepository(
                $repository
            );
        }));

        ServiceRegister::registerService(TaskCleanupRepository::class, new SingleInstance(static function () {
            /** @var QueueItemRepository $repository */
            $repository = RepositoryRegistry::getRepository(QueueItem::class);
            return new TaskCleanupRepository(
                $repository
            );
        }));
    }

    /**
     * @return void
     */
    protected static function initControllers(): void
    {
        ServiceRegister::registerService(PaymentMethodsController::class, new SingleInstance(static function () {
            return new PaymentMethodsController(
                ServiceRegister::getService(PaymentMethodService::class),
                ServiceRegister::getService(HostedTokenizationService::class)
            );
        }));
        ServiceRegister::registerService(HostedTokenizationController::class,
            new SingleInstance(static function () {
                return new HostedTokenizationController(
                    ServiceRegister::getService(HostedTokenizationService::class)
                );
            }));

        ServiceRegister::registerService(HostedCheckoutController::class, new SingleInstance(static function () {
            return new HostedCheckoutController(
                ServiceRegister::getService(HostedCheckoutService::class),
            );
        }));

        ServiceRegister::registerService(CheckoutAPIPaymentController::class, new SingleInstance(static function () {
            return new CheckoutAPIPaymentController(
                ServiceRegister::getService(WaitPaymentOutcomeProcess::class),
                ServiceRegister::getService(PaymentTransactionRepositoryInterface::class)
            );
        }));

        ServiceRegister::registerService(ConnectionController::class, new SingleInstance(static function () {
            return new ConnectionController(
                ServiceRegister::getService(ConnectionService::class)
            );
        }));
        ServiceRegister::registerService(VersionController::class, new SingleInstance(static function () {
            return new VersionController(
                ServiceRegister::getService(VersionService::class)
            );
        }));
        ServiceRegister::registerService(StoreController::class, new SingleInstance(static function () {
            return new StoreController(
                ServiceRegister::getService(StoreService::class)
            );
        }));
        ServiceRegister::registerService(IntegrationController::class, new SingleInstance(static function () {
            return new IntegrationController(
                ServiceRegister::getService(ConnectionService::class)
            );
        }));

        ServiceRegister::registerService(PaymentController::class, new SingleInstance(static function () {
            return new PaymentController(
                ServiceRegister::getService(PaymentService::class),
                ServiceRegister::getService(ShopPaymentService::class)
            );
        }));

        ServiceRegister::registerService(LanguageController::class, new SingleInstance(static function () {
            return new LanguageController(
                ServiceRegister::getService(LanguageService::class)
            );
        }));

        ServiceRegister::registerService(GeneralSettingsController::class, new SingleInstance(static function () {
            return new GeneralSettingsController(
                ServiceRegister::getService(GeneralSettingsService::class),
                ServiceRegister::getService(DisconnectService::class)
            );
        }));

        ServiceRegister::registerService(ProductTypesController::class, new SingleInstance(static function () {
            return new ProductTypesController(
                ServiceRegister::getService(ProductTypeService::class)
            );
        }));

        ServiceRegister::registerService(WebhooksController::class, new SingleInstance(static function () {
            return new WebhooksController(
                ServiceRegister::getService(WebhookTransformerInterface::class),
                ServiceRegister::getService(WebhookService::class),
                ServiceRegister::getService(WebhookLogsService::class)
            );
        }));

        ServiceRegister::registerService(LogsController::class, new SingleInstance(static function () {
            return new LogsController(
                ServiceRegister::getService(MonitoringLogsService::class),
                ServiceRegister::getService(WebhookLogsService::class)
            );
        }));

        ServiceRegister::registerService(PaymentLinksController::class, new SingleInstance(static function () {
            return new PaymentLinksController(
                ServiceRegister::getService(PaymentLinksService::class),
            );
        }));

        ServiceRegister::registerService(OrderController::class, new SingleInstance(static function () {
            return new OrderController(
                ServiceRegister::getService(OrderService::class),
            );
        }));

        ServiceRegister::registerService(CaptureController::class, new SingleInstance(static function () {
            return new CaptureController(
                ServiceRegister::getService(CaptureService::class),
            );
        }));

        ServiceRegister::registerService(CancelController::class, new SingleInstance(static function () {
            return new CancelController(
                ServiceRegister::getService(CancelService::class),
            );
        }));

        ServiceRegister::registerService(RefundController::class, new SingleInstance(static function () {
            return new RefundController(
                ServiceRegister::getService(RefundService::class),
            );
        }));
    }

    protected static function initProxies(): void
    {
        ServiceRegister::registerService(PaymentMethodProxyInterface::class,
            new SingleInstance(static function () {
                return new PaymentMethodProxy(
                    ServiceRegister::getService(MerchantClientFactory::class)
                );
            }));

        ServiceRegister::registerService(PaymentsProxyInterface::class,
            new SingleInstance(static function () {
                return new PaymentsProxy(
                    ServiceRegister::getService(MerchantClientFactory::class)
                );
            }));

        ServiceRegister::registerService(HostedCheckoutProxyInterface::class,
            new SingleInstance(static function () {
                return new HostedCheckoutProxy(
                    ServiceRegister::getService(MerchantClientFactory::class)
                );
            })
        );

        ServiceRegister::registerService(HostedTokenizationProxyInterface::class,
            new SingleInstance(static function () {
                return new HostedTokenizationProxy(
                    ServiceRegister::getService(MerchantClientFactory::class)
                );
            }));

        ServiceRegister::registerService(
            ConnectionProxyInterface::class,
            new SingleInstance(static function () {
                return new ConnectionProxy(
                    ServiceRegister::getService(MerchantClientFactory::class)
                );
            })
        );

        ServiceRegister::registerService(PaymentLinksProxyInterface::class,
            new SingleInstance(static function () {
                return new PaymentLinksProxy(
                    ServiceRegister::getService(MerchantClientFactory::class)
                );
            })
        );

        ServiceRegister::registerService(
            SurchargeProxyInterface::class,
            new SingleInstance(static function () {
                return new SurchargeProxy(
                    ServiceRegister::getService(MerchantClientFactory::class)
                );
            })
        );

        ServiceRegister::registerService(
            CaptureProxyInterface::class,
            new SingleInstance(static function () {
                return new CaptureProxy(
                    ServiceRegister::getService(MerchantClientFactory::class)
                );
            })
        );

        ServiceRegister::registerService(
            CancelProxyInterface::class,
            new SingleInstance(static function () {
                return new CancelProxy(
                    ServiceRegister::getService(MerchantClientFactory::class)
                );
            })
        );

        ServiceRegister::registerService(
            RefundProxyInterface::class,
            new SingleInstance(static function () {
                return new RefundProxy(
                    ServiceRegister::getService(MerchantClientFactory::class)
                );
            })
        );
    }

    protected static function initRequestProcessors(): void
    {
    }

    protected static function initBackgroundProcesses(): void
    {
        ServiceRegister::registerService(
            WaitPaymentOutcomeProcessStarterInterface::class,
            new SingleInstance(static function () {
                return new WaitPaymentOutcomeProcessStarter(
                    ServiceRegister::getService(AsyncProcessService::class),
                    StoreContext::getInstance()
                );
            })
        );

        ServiceRegister::registerService(
            TransactionStatusCheckListener::class,
            new SingleInstance(static function () {
                return new TransactionStatusCheckListener(
                    ServiceRegister::getService(QueueService::class),
                    ServiceRegister::getService(TimeProviderInterface::class),
                );
            })
        );

        ServiceRegister::registerService(
            AutoCaptureCheckListener::class,
            new SingleInstance(static function () {
                return new AutoCaptureCheckListener(
                    ServiceRegister::getService(QueueService::class),
                    ServiceRegister::getService(TimeProviderInterface::class),
                );
            })
        );
    }

    /**
     * @return void
     */
    protected static function initEvents()
    {
        parent::initEvents();

        /** @var EventBus $eventBus */
        $eventBus = ServiceRegister::getService(EventBus::class);

        $eventBus->when(
            TickEvent::class,
            static function () {
                /** @var TransactionStatusCheckListener $listener */
                $listener = ServiceRegister::getService(TransactionStatusCheckListener::class);
                $listener->handle();
            }
        );

        $eventBus->when(
            TickEvent::class,
            static function () {
                /** @var AutoCaptureCheckListener $listener */
                $listener = ServiceRegister::getService(AutoCaptureCheckListener::class);
                $listener->handle();
            }
        );

        $eventBus->when(
            TickEvent::class,
            static function () {
                (new TaskCleanupListener())->handle();
            }
        );
    }
}