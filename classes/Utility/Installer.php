<?php

namespace OnlinePayments\Classes\Utility;

use Configuration;
use Exception;
use Language;
use OnlinePayments\Classes\Repositories\BaseRepository;
use OnlinePayments\Classes\Repositories\MonitoringLogsRepository;
use OnlinePayments\Classes\Repositories\PaymentTransactionsRepository;
use OnlinePayments\Classes\Repositories\ProductTypesRepository;
use OnlinePayments\Classes\Repositories\QueueItemRepository;
use OnlinePayments\Classes\Repositories\TokensRepository;
use OnlinePayments\Classes\Repositories\WebhookLogsRepository;
use OnlinePayments\Classes\Services\ImageHandler;
use OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Disconnect\DisconnectService;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\BusinessLogic\Domain\Stores\StoreService;
use OnlinePayments\Core\Infrastructure\Logger\Logger;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OrderState;
use PaymentModule;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tab;
use Validate;

/**
 * Class Installer
 *
 * @package OnlinePayments\Classes\Utility
 */
class Installer
{
    private static array $controllers = [
        'OnlinePaymentsConnection',
        'OnlinePaymentsState',
        'OnlinePaymentsStores',
        'OnlinePaymentsVersion',
        'OnlinePaymentsPayment',
        'OnlinePaymentsLanguage',
        'OnlinePaymentsGeneralSettings',
        'OnlinePaymentsOrderStatuses',
        'OnlinePaymentsMonitoring',
        'OnlinePaymentsTransaction'
    ];
    private static array $orderStatuses = [
        [
            'configKey' => 'WOP_PENDING_ORDER_STATUS_ID',
            'color' => '#34209E',
            'logo' => 'icon_WOP_PENDING_ORDER_STATUS.gif',
            'template' => '',
            'send_email' => 0,
            'invoice' => 0,
            'logable' => 0,
            'deleted' => 0,
            'names' => [
                'fr' => 'En attente de la confirmation de paiement',
                'en' => 'Awaiting payment confirmation',
                'es' => 'Esperando confirmación del pago',
                'it' => 'In attesa della conferma di pagamento',
                'nl' => 'In afwachting van betalingsbevestiging',
                'de' => 'Warten auf Zahlungsbestätigung',
            ]
        ],
        [
            'configKey' => 'WOP_AWAITING_CAPTURE_STATUS_ID',
            'color' => '#3498D8',
            'logo' => 'icon_WOP_AWAITING_CAPTURE_STATUS.gif',
            'template' => '',
            'send_email' => 0,
            'invoice' => 0,
            'logable' => 0,
            'deleted' => 0,
            'names' => [
                'fr' => 'En attente de la remise en banque du paiement',
                'en' => 'Awaiting payment capture',
                'es' => 'Esperando captura del pago',
                'it' => 'In attesa di ricevere il pagamento',
                'nl' => 'In afwachting van betalingsvastlegging',
                'de' => 'Warten auf Zahlungserfassung',
            ]
        ]
    ];
    private static array $hooks = [
        'paymentOptions',
        'displayAdminOrderMainBottom',
        'displayPaymentByBinaries',
        'displayPaymentTop',
        'actionFrontControllerSetMedia',
        'actionAdminControllerSetMedia',
        'customerAccount',
        'displayPDFInvoice',
        'displayAdminProductsExtra',
        'actionProductUpdate',
        'actionOrderSlipAdd',
        'actionOrderStatusUpdate',
        'displayBackOfficeHeader',
        'actionValidateOrder'
    ];
    private static array $deprecated_hooks = [
        'displayAdminOrderLeft'
    ];
    private PaymentModule $module;

    /**
     * @param PaymentModule $module
     */
    public function __construct(PaymentModule $module)
    {
        $this->module = $module;
    }

    /**
     * @throws Exception
     */
    public function install(): void
    {
        $this->createTables();
        $this->addControllers();
        $this->addHooks();
        $this->installOrderStatuses(self::$orderStatuses, $this->module->name);
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     * @throws Exception
     */
    public function uninstall(): void
    {
        $this->removeImages();
        $this->disconnect();
        $this->dropTables();
        $this->removeControllers();
    }

    /**
     * Registers module hooks.
     *
     * @return void
     *
     * @throws Exception
     */
    public function addHooks(): void
    {
        foreach (self::$deprecated_hooks as $hook) {
            $this->module->unregisterHook($hook);
        }

        foreach (self::$hooks as $hook) {
            $this->addHook($hook);
        }
    }

    /**
     * @param string $hook
     *
     * @return void
     *
     * @throws Exception
     */
    public function addHook(string $hook): void
    {
        $result = $this->module->registerHook($hook);
        if (!$result) {
            Logger::logError('Online Payments plugin failed to register hook: ' . $hook);
        }
    }

    /**
     * Registers Admin controller.
     *
     * @param string $name Controller name
     * @param int $parentId ID of parent controller
     *
     * @return void
     *
     * @throws Exception
     */
    public function addController(string $name, int $parentId = -1): void
    {
        $tab = new Tab();

        $tab->active = 1;
        $tab->name[(int)Configuration::get('PS_LANG_DEFAULT')] = $this->module->name;
        $tab->class_name = $name;
        $tab->module = $this->module->name;
        $tab->id_parent = $parentId;
        $success = $tab->add();

        if (!$success) {
            throw new Exception('Online Payments plugin failed to register controller: ' . $name);
        }
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     * @throws Exception
     */
    public function removeControllers(): void
    {
        /** @var Tab[] $tabs */
        $tabs = Tab::getCollectionFromModule($this->module->name);
        if ($tabs && count($tabs)) {
            foreach ($tabs as $tab) {
                $success = $tab->delete();

                if (!$success) {
                    throw new Exception($this->module->name . 'plugin failed to remove controller: ' . $tab->name);
                }
            }
        }
    }

    public function removeHooks()
    {
        foreach (self::$deprecated_hooks as $hook) {
            $this->module->unregisterHook($hook);
        }

        foreach (self::$hooks as $hook) {
            $this->module->unregisterHook($hook);
        }
    }

    /**
     * @throws Exception
     */
    private function createTables(): void
    {
        /** @var ActiveBrandProviderInterface $provider */
        $provider = ServiceRegister::getService(ActiveBrandProviderInterface::class);

        $this->createTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . BaseRepository::TABLE_NAME, 9);
        $this->createTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . QueueItemRepository::TABLE_NAME, 9);
        $this->createTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . MonitoringLogsRepository::TABLE_NAME, 9);
        $this->createTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . WebhookLogsRepository::TABLE_NAME, 9);
        $this->createTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . TokensRepository::TABLE_NAME, 9);
        $this->createTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . ProductTypesRepository::TABLE_NAME, 9);
        $this->createTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . PaymentTransactionsRepository::TABLE_NAME, 11);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    private function dropTables(): void
    {
        /** @var ActiveBrandProviderInterface $provider */
        $provider = ServiceRegister::getService(ActiveBrandProviderInterface::class);
        $this->dropTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . BaseRepository::TABLE_NAME);
        $this->dropTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . QueueItemRepository::TABLE_NAME);
        $this->dropTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . MonitoringLogsRepository::TABLE_NAME);
        $this->dropTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . WebhookLogsRepository::TABLE_NAME);
        $this->dropTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . TokensRepository::TABLE_NAME);
        $this->dropTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . ProductTypesRepository::TABLE_NAME);
        $this->dropTable(strtolower($provider->getActiveBrand()->getCode()) . '_' . PaymentTransactionsRepository::TABLE_NAME);
    }

    /**
     * Registers module Admin controllers.
     *
     * @return void
     *
     * @throws Exception
     */
    private function addControllers(): void
    {
        foreach (self::$controllers as $controller) {
            $this->addController($controller);
        }
    }

    /**
     * @param string $tableName
     * @param int $indexNumber
     *
     * @return void
     *
     * @throws Exception
     */
    private function createTable(string $tableName, int $indexNumber): void
    {
        $createdTable = DatabaseHandler::createTable($tableName, $indexNumber);

        if (!$createdTable) {
            throw new Exception('Online Payments plugin failed to create table: ' . $tableName);
        }
    }

    /**
     * @param string $tableName
     *
     * @return void
     *
     * @throws Exception
     */
    private function dropTable(string $tableName): void
    {
        $createdTable = DatabaseHandler::dropTable($tableName);

        if (!$createdTable) {
            throw new Exception('Online Payments plugin failed to drop table: ' . $tableName);
        }
    }

    /**
     * @param array $orderStatuses
     * @param string $moduleName
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function installOrderStatuses(array $orderStatuses, string $moduleName)
    {
        foreach ($orderStatuses as $orderStatus) {
            $this->createOrderStatus($orderStatus, $moduleName);
        }
    }

    /**
     * @param array $orderStatus
     * @param string $moduleName
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function createOrderStatus(array $orderStatus, string $moduleName)
    {
        $orderState = new OrderState(Configuration::getGlobalValue($orderStatus['configKey']));
        if (!Validate::isLoadedObject($orderState) || $orderState->deleted) {
            $orderState->hydrate($orderStatus);
            $orderState->name = [];
            $orderState->module_name = pSQL($moduleName);
            $languages = Language::getLanguages(false);
            $names = $orderStatus['names'];
            foreach ($languages as $language) {
                $name = isset($names[$language['iso_code']]) ? $names[$language['iso_code']] : $names['en'];
                $orderState->name[(int)$language['id_lang']] = pSQL($name);
            }
            if ($orderState->save()) {
                if ($orderStatus['logo']) {
                    $source = realpath(_PS_MODULE_DIR_ . $moduleName . '/views/img/icons/' . $orderStatus['logo']);
                    $destination = _PS_ROOT_DIR_ . '/img/os/' . (int)$orderState->id . '.gif';
                    Tools::copy($source, $destination);
                }
                Configuration::updateGlobalValue($orderStatus['configKey'], (int)$orderState->id);
            }
        }
    }

    private function disconnect()
    {
        try {
            /** @var DisconnectService $disconnectService */
            $disconnectService = ServiceRegister::getService(DisconnectService::class);
            $connectedStores = $this->getStoreService()->getStores();

            foreach ($connectedStores as $store) {
                StoreContext::doWithStore(
                    $store->getStoreId(),
                    function () use ($disconnectService) {
                        $disconnectService->disconnect();
                    }
                );
            }
        } catch (\Throwable $e) {
            Logger::logWarning('Failed to disconnect merchant account because ' . $e->getMessage());
        }
    }

    /**
     * Removes images for payment methods and adyen giving for all connected shops.
     *
     * @return void
     *
     * @throws Exception
     */
    private function removeImages(): void
    {
        $connectedStores = $this->getStoreService()->getStores();
        foreach ($connectedStores as $store) {
            StoreContext::doWithStore(
                $store->getStoreId(),
                function () {
                    $this->doRemoveImages();
                }
            );
        }

        ImageHandler::removeOnlinePaymentsDirectory();
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    private function doRemoveImages(): void
    {
        $storeId = StoreContext::getInstance()->getStoreId();
        ImageHandler::removeDirectoryForStore($storeId, (string)ConnectionMode::live());
        ImageHandler::removeDirectoryForStore($storeId, (string)ConnectionMode::test());
    }

    private function getStoreService(): StoreService
    {
        return ServiceRegister::getService(StoreService::class);
    }
}
