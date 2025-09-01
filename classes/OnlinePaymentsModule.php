<?php

namespace OnlinePayments\Classes;

use DateInterval;
use DateTime;
use OnlinePayments\Classes\Services\Checkout\PaymentOptionsService;
use OnlinePayments\Classes\Services\OrderStatusMappingService;
use OnlinePayments\Classes\Services\PaymentLink\OrderProviderService;
use OnlinePayments\Classes\Services\PrestaShop\CancelService;
use OnlinePayments\Classes\Services\PrestaShop\OrderService;
use OnlinePayments\Classes\Services\PrestaShop\RefundService;
use OnlinePayments\Classes\Utility\Installer;
use OnlinePayments\Classes\Utility\SessionService;
use OnlinePayments\Classes\Utility\Url;
use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use OnlinePayments\Core\Branding\Brand\BrandConfig;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkRequest;
use OnlinePayments\Core\BusinessLogic\Domain\ProductTypes\ProductType;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use OnlinePayments\Core\Infrastructure\Utility\TimeProvider;
use PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

/**
 * Class OnlinePaymentsModule
 *
 * @package OnlinePayments\Classes
 */
class OnlinePaymentsModule extends \PaymentModule
{
    /** @var string */
    public $theme;
    /** @var \OnlinePayments\Core\Infrastructure\Logger\Logger */
    public $logger;
    private ?ServiceContainer $serviceContainer = null;

    public function install()
    {
        try {
            $success = parent::install();
            $success && $this->getInstaller()->install();
        } catch (\Exception $e) {
            \OnlinePayments\Core\Infrastructure\Logger\Logger::logError(
                'Failed to complete installation because ' . $e->getMessage()
            );

            return false;
        }

        return $success;
    }

    /**
     * Creates plugin installer.
     *
     * @return Installer
     */
    public function getInstaller(): Installer
    {
        return new Installer($this);
    }

    /**
     * @param string $serviceName
     *
     * @return mixed
     */
    public function getService(string $serviceName)
    {
        if ($this->serviceContainer === null) {
            $this->serviceContainer = new ServiceContainer(
                $this->name . str_replace('.', '', $this->version),
                $this->getLocalPath()
            );
        }

        return $this->serviceContainer->getService($serviceName);
    }

    /**
     * @return \Monolog\Logger
     */
    public function getLogger()
    {
        static $logger;

        if (null === $logger) {
            /** @var \Monolog\Logger $logger */
            $logger = $this->getService('worldlineop.logger');
        }

        return $logger;
    }

    /**
     * @return bool
     */
    public function uninstall(): bool
    {
        try {
            $success = parent::uninstall();
            $success && $this->getInstaller()->uninstall();

            return $success;
        } catch (\Throwable $e) {
            $this->_errors[] = $e->getMessage();
            \PrestaShopLogger::addLog(
                'Online Payments plugin uninstallation failed. Error: ' . $e->getMessage() . ' . Trace: ' . $e->getTraceAsString(
                ),
                \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR
            );

            return false;
        }
    }

    /**
     * @throws \PrestaShopException
     */
    public function getContent()
    {
        $isShopContext = \Shop::getContext() === \Shop::CONTEXT_SHOP;

        if (!$isShopContext) {
            $this->context->controller->errors[] = $this->l('Please select the specific shop to configure.');

            return '';
        }

        if (!$this->isEnabledForShopContext()) {
            $this->context->controller->errors[] = $this->l('Please enable the module.');

            return '';
        }

        $wakeupService = ServiceRegister::getService(
            TaskRunnerWakeup::class
        );
        $wakeupService->wakeup();

        $this->loadStyles();
        $this->loadScripts();
        $brand = $this->getBrand();

        $this->context->smarty->assign(
            [
                'urls' => $this->getUrls(),
                'translations' => $this->getTranslations(),
                '' => 'brand',
                'brand' => [
                    'name' => $brand->getName(),
                    'code' => $brand->getCode(),
                ],
            ]
        );

        return $this->display($this->getLocalPath(), 'views/templates/index.tpl');
    }

    private function loadStyles(): void
    {
        $this->context->controller->addCSS(
            [
                $this->getPathUri() . '/views/css/index.css',
                $this->getPathUri() . '/views/css/op-admin.css',
            ],
            'all',
            null,
            false
        );
    }

    private function loadScripts(): void
    {
        $this->context->controller->addJS(
            [
                $this->getPathUri() . '/views/js/index.js',
            ],
            false
        );
    }

    private function getBrand(): BrandConfig
    {
        /** @var \OnlinePayments\Core\Branding\Brand\ActiveBrandProvider $provider */
        $provider = ServiceRegister::getService(
            ActiveBrandProviderInterface::class
        );

        return $provider->getActiveBrand();
    }

    private function getUrls(): array
    {
        return [
            'connection' => [
                'getSettingsUrl' => Url::getAdminUrl(
                    'OnlinePaymentsConnection',
                    'getConnectionSettings',
                    '{storeId}'
                ),
                'submitUrl' => Url::getAdminUrl(
                    'OnlinePaymentsConnection',
                    'connect',
                    '{storeId}'
                ),
                'webhooksUrl' => Url::getFrontUrl(
                        'webhook'
                    ) . '?storeId={storeId}',
            ],
            'stores' => [
                'storesUrl' => Url::getAdminUrl(
                    'OnlinePaymentsStores',
                    'getStores',
                    '{storeId}'
                ),
                'currentStoreUrl' => Url::getAdminUrl(
                    'OnlinePaymentsStores',
                    'getCurrentStore',
                ),
            ],
            'integration' => [
                'stateUrl' => Url::getAdminUrl(
                    'OnlinePaymentsState',
                    'index',
                    '{storeId}'
                )
            ],
            'version' => [
                'versionUrl' => Url::getAdminUrl(
                    'OnlinePaymentsVersion',
                    'getVersion'
                )
            ],
            'payments' => [
                'getAvailablePaymentsUrl' => Url::getAdminUrl(
                    'OnlinePaymentsPayment',
                    'list',
                    '{storeId}'
                ),
                'enableMethodUrl' => Url::getAdminUrl(
                    'OnlinePaymentsPayment',
                    'enable',
                    '{storeId}'
                ),
                'saveMethodConfigurationUrl' => Url::getAdminUrl(
                    'OnlinePaymentsPayment',
                    'save',
                    '{storeId}',
                    '{methodId}'
                ),
                'getMethodConfigurationUrl' => Url::getAdminUrl(
                    'OnlinePaymentsPayment',
                    'getPaymentMethod',
                    '{storeId}',
                    '{methodId}'
                ),
                'getLanguagesUrl' => Url::getAdminUrl(
                    'OnlinePaymentsLanguage',
                    'getLanguages',
                    '{storeId}'
                )
            ],
            'settings' => [
                'getGeneralSettingsUrl' => Url::getAdminUrl(
                    'OnlinePaymentsGeneralSettings',
                    'getGeneralSettings',
                    '{storeId}'
                ),
                'getPaymentStatusesUrl' => Url::getAdminUrl(
                    'OnlinePaymentsOrderStatuses',
                    'getOrderStatuses',
                    '{storeId}'
                ),
                'saveConnectionUrl' => Url::getAdminUrl(
                    'OnlinePaymentsConnection',
                    'connect',
                    '{storeId}'
                ),
                'saveCardsSettingsUrl' => Url::getAdminUrl(
                    'OnlinePaymentsGeneralSettings',
                    'saveCardsSettings',
                    '{storeId}'
                ),
                'savePaymentSettingsUrl' => Url::getAdminUrl(
                    'OnlinePaymentsGeneralSettings',
                    'savePaymentSettings',
                    '{storeId}',
                ),
                'saveLogSettingsUrl' => Url::getAdminUrl(
                    'OnlinePaymentsGeneralSettings',
                    'saveLogSettings',
                    '{storeId}',
                ),
                'savePayByLinkSettingsUrl' => Url::getAdminUrl(
                    'OnlinePaymentsGeneralSettings',
                    'savePayByLinkSettings',
                    '{storeId}',
                ),
                'webhooksUrl' => Url::getFrontUrl(
                        'webhook'
                    ) . '?storeId={storeId}',
                'disconnectUrl' => Url::getAdminUrl(
                    'OnlinePaymentsGeneralSettings',
                    'disconnect',
                    '{storeId}'
                )
            ],
            'monitoring' => [
                'getMonitoringLogsUrl' => Url::getAdminUrl(
                    'OnlinePaymentsMonitoring',
                    'getMonitoringLogs',
                    '{storeId}'
                ),
                'getWebhookLogsUrl' => Url::getAdminUrl(
                    'OnlinePaymentsMonitoring',
                    'getWebhookLogs',
                    '{storeId}'
                ),
                'downloadMonitoringLogsUrl' => Url::getAdminUrl(
                    'OnlinePaymentsMonitoring',
                    'downloadMonitoringLogs',
                    '{storeId}'
                ),
                'downloadWebhookLogsUrl' => Url::getAdminUrl(
                    'OnlinePaymentsMonitoring',
                    'downloadWebhookLogs',
                    '{storeId}'
                )
            ]
        ];
    }

    private function getTranslations(): array
    {
        return [
            'default' => $this->getDefaultTranslations(),
            'current' => $this->getCurrentTranslations(),
        ];
    }

    private function getDefaultTranslations()
    {
        $baseDir = __DIR__ . '/../views/lang/';

        return json_decode(file_get_contents($baseDir . 'en.json'), true);
    }

    private function getCurrentTranslations()
    {
        $baseDir = __DIR__ . '/../views/lang/';
        $locale = $this->getLocale();
        $file = file_exists($baseDir . $locale . '.json') ? $baseDir . $locale . '.json' : $baseDir . 'en.json';

        return json_decode(file_get_contents($file), true);
    }

    /**
     * @return string
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function getLocale(): string
    {
        $locale = new \Language(\Context::getContext()->employee->id_lang);

        return in_array($locale->iso_code, ['en', 'de', 'fr', 'nl', 'it', 'es']) ? $locale->iso_code : 'en';
    }

    public function hookActionFrontControllerSetMedia()
    {
        $controller = \Tools::getValue('controller');

        switch ($controller) {
            case 'order':
                $this->context->controller->registerJavascript(
                    'worldineoc-js-sdk',
                    'https://payment.preprod.direct.ingenico.com/hostedtokenization/js/client/tokenizer.min.js',
                    ['server' => 'remote', 'priority' => 1, 'position' => 'head', 'attribute' => 'defer']
                );
                $this->context->controller->registerStylesheet(
                    'worldlineop-css-paymentOptions',
                    $this->getPathUri() . 'views/css/front.css?version=' . $this->version,
                    ['server' => 'remote']
                );
                $this->context->controller->registerJavascript(
                    'worldlineop-js-paymentOptions',
                    $this->getPathUri() . 'views/js/paymentOptions.js?version=' . $this->version,
                    ['position' => 'head', 'priority' => 1000, 'server' => 'remote']
                );
                break;
            case 'redirect':
                $this->context->controller->registerJavascript(
                    'worldlineop-redirect-javascript',
                    $this->getPathUri() . 'views/js/redirect.js',
                    ['position' => 'bottom', 'priority' => 1000, 'server' => 'remote']
                );
                break;
        }
    }

    /**
     * @throws \PrestaShopException
     */
    public function hookActionAdminControllerSetMedia()
    {
        if (\Tools::getValue('controller') == 'AdminOrders') {
            \Media::addJsDef([
                'worldlineopAjaxTransactionUrl' => $this->context->link->getAdminLink(
                    'AdminWorldlineopAjaxTransaction',
                    true,
                    [],
                    ['ajax' => 1, 'token' => \Tools::getAdminTokenLite('AdminWorldlineopAjaxTransaction')]
                ),
                'worldlineopGenericErrorMessage' => $this->l('An error occurred while processing your request. Please try again.'),
                'alertRefund' => $this->l('Do you confirm the refund of the funds?'),
                'alertCapture' => $this->l('Do you confirm the capture of the transaction?'),
                'alertCancel' => $this->l('Do you confirm the cancellation of the transaction?'),
            ]);

            $this->context->controller->addJS(
                [
                    $this->getPathUri() . 'views/js/admin/worldlineop-order-tab-content.js',
                    $this->getPathUri() . 'views/js/admin/worldlineop-back-office-order.js',
                ]
            );
        }
    }

    /**
     * @return array
     */
    public function hookPaymentOptions()
    {
        try {
            /** @var PaymentOptionsService $paymentOptionsService */
            $paymentOptionsService = ServiceRegister::getService(PaymentOptionsService::class);

            return $paymentOptionsService->getAvailable();
        } catch (\Throwable $e) {
            $this->getLogger()->error('Error while presenting payment options', ['message' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * @return string
     */
    public function hookDisplayPaymentByBinaries()
    {
        return $this->context->smarty->fetch($this->getLocalPath() . '/views/templates/front/hookDisplayPaymentByBinaries.tpl');
    }

    /**
     * @param $params
     *
     * @return string
     */
    public function hookDisplayPaymentTop($params)
    {
        if (\Tools::getValue('worldlineopDisplayPaymentTopMessage')) {
            return $this->context->smarty->fetch($this->getLocalPath() . '/views/templates/front/hookDisplayPaymentTop.tpl');
        }

        return '';
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function hookCustomerAccount($params)
    {
        return $this->display($this->getLocalPath(), 'views/templates/front/hookCustomerAccount.tpl');
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayAdminOrderMainBottom($params)
    {
        try {
            $html = $this->hookAdminOrderCommon(\Tools::getValue('id_order'));
        } catch (\Exception $e) {
            return '';
        }
        $this->context->smarty->assign([
            'html' => $html,
        ]);

        return $this->display($this->getLocalPath(), 'views/templates/admin/hookAdminOrder_container.tpl');
    }

    /**
     * @param int $idOrder
     *
     * @return string
     *
     * @throws \Exception
     */
    public function hookAdminOrderCommon($idOrder)
    {
        $order = new \Order((int)$idOrder);
        if (!\Validate::isLoadedObject($order)/* || $order->module !== $this->name*/) {
            throw new \Exception("Module $this->name: Cannot load order");
        }

        if ($order->id_shop != $this->context->shop->id || \Shop::getContext() !== \Shop::CONTEXT_SHOP) {
            return $this->displayError(sprintf($this->l('Please change shop context to shop ID %d'), $order->id_shop));
        }
        try {
            $orderService = new OrderService($this->name, $this->context->shop->id);

            $this->context->smarty->assign($orderService->getDetails($idOrder));
        } catch (\Exception $e) {
            return $this->displayError($e->getMessage());
        }

        return $this->display($this->getLocalPath(), 'views/templates/admin/hookAdminOrder.tpl');
    }

    /**
     * @param array $params
     *
     * @return string
     *
     * @throws \PrestaShopException
     */
    public function hookDisplayPDFInvoice($params)
    {
        /** @var \OrderInvoice $invoice */
        $invoice = $params['object'];
        $order = new \Order((int)$invoice->id_order);
        if (!\Validate::isLoadedObject($order)) {
            return '';
        }

        $response = CheckoutAPI::get()->payment($order->id_shop)->getPaymentTransaction($order->id_cart);
        if (!$response->isSuccessful() || null === $response->getPaymentTransaction()) {
            return '';
        }

        $this->context->smarty->assign([
            'worldlineop_transaction_id' => $response->getPaymentTransaction()->getPaymentId()->getTransactionId(),
        ]);

        return $this->display($this->getLocalPath(), 'views/templates/admin/hookDisplayPDFInvoice.tpl');
    }

    /**
     * @param mixed[] $params
     *
     * @return string
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $idProduct = (string)$params['id_product'];
        $result = AdminAPI::get()->productTypes()->list($idProduct);
        if (!$result->isSuccessful()) {
            return '';
        }

        $this->context->smarty->assign([
            'availableProductTypes' => [
                (string)ProductType::foodAndDrink() => $this->l('Food & Drink'),
                (string)ProductType::homeAndGarden() => $this->l('Home & Garden'),
                (string)ProductType::giftAndFlowers() => $this->l('Gift & Flowers'),
            ],
            'module' => $this->name,
            'selectedProductType' => (string)$result->getSelectedProductType(),
        ]);

        return $this->display($this->getLocalPath(), 'views/templates/admin/hookDisplayAdminProductsExtra.tpl');
    }

    /**
     * @param mixed[] $params
     *
     * @return void
     */
    public function hookActionProductUpdate($params)
    {
        if ($form = \Tools::getValue($this->name)) {
            $idProduct = $params['id_product'];
            try {
                AdminAPI::get()->productTypes()->save($idProduct, ProductType::parse((string)$form['product_type']));
            } catch (\Throwable $e) {
                AdminAPI::get()->productTypes()->delete($idProduct);
            }
        }
    }

    /**
     * Hook for handling partial refund through Return products option.
     *
     * @param array $params Array containing order, cart and product list of partial refund
     */
    public function hookActionOrderSlipAdd(array $params)
    {
        $order = $params['order'];

        if ($order->module !== $this->name) {
            return;
        }

        $shopRefundService = new RefundService($this->name, $this->context->shop->id);

        $error = $shopRefundService->handleStandard((string) $params['cart']->id, $params['order']);

        if (!empty($error)) {
            setcookie('error', $error, time() + 3600, '/');
            \Tools::redirectAdmin($this->generateOrderPageUrl($order));
        }
    }

    public function hookActionOrderStatusUpdate(array $params): void
    {
        $order = new \Order($params['id_order']);
        $newOrderStatus = $params['newOrderStatus'];

        if ($order->module !== $this->name || $newOrderStatus->id == $order->current_state ||
            $newOrderStatus->id != OrderStatusMappingService::PRESTA_CANCELED_ID) {
            return;
        }

        $cartId = \Cart::getCartIdByOrderId($order->id);

        $cancelService = new CancelService($this->name, $this->context->shop->id);
        $cancelService->handle((string)$cartId, $order);
    }

    /**
     * Hook for displaying header data used in BO.
     *
     * @return false|string Header HTML data as string
     */
    public function hookDisplayBackOfficeHeader(): string
    {
        if (!$this->isEnabled($this->name) || \Tools::getValue('controller') !== 'AdminOrders') {
            return '';
        }

        $generalSettings = AdminAPI::get()->generalSettings($this->context->shop->id)->getGeneralSettings();
        $generalSettingsArray = $generalSettings->toArray()['payByLinkSettings'];

        if (!$generalSettings->isSuccessful() || !$generalSettingsArray || !$generalSettingsArray['enabled']) {
            return '';
        }

        $expirationDate = TimeProvider::getInstance()->getCurrentLocalTime()->add(
            new DateInterval('P' . $generalSettingsArray['expirationTime'] . 'D')
        )->format("Y-m-d");

        $this->context->smarty->assign([
            'worldlinePayByLinkTitle' => $generalSettingsArray['title'],
            'worldlineExpirationDate' => $expirationDate
        ]);

        return $this->display($this->getLocalPath(), 'views/templates/hook/worldlineop-backoffice-order-creation.tpl');
    }

    public function hookActionValidateOrder(array $params): void
    {
        if (!isset($this->context->controller) ||
            'admin' !== $this->context->controller->controller_type ||
            $params['order']->module !== $this->name) {

            return;
        }

        $expiresAt = \Tools::getValue('worldline-expires-at-date');
        /** @var \Order $order */
        $order = $params['order'];

        $cartId = \Cart::getCartIdByOrderId($order->id);

        $paymentLinkResponse = AdminAPI::get()->paymentLinks($this->context->shop->id)->create(new PaymentLinkRequest(
            new OrderProviderService((string)$order->id),
            $this->context->link->getModuleLink(
                $this->name,
                'redirect',
                ['action' => 'redirectReturnPaymentLink', 'merchantReference' => $cartId]
            ),
            new DateTime($expiresAt)
        ));

        if ($paymentLinkResponse->isSuccessful()) {
            SessionService::set(
                'successMessage',
                $this->l('Payment link successfully generated.')
            );

            return;
        }

        SessionService::set(
            'errorMessage',
            $this->l('Payment link generation failed. Reason: ') . $paymentLinkResponse->toArray()['errorMessage'] ?? ''
        );
    }

    private function generateOrderPageUrl(\Order $order): string
    {
        return SymfonyContainer::getInstance()->get('router')
            ->generate('admin_orders_view', ['orderId' => $order->id]);
    }
}
