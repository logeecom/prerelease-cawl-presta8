<?php

namespace CAWL\OnlinePayments\Classes;

use DateInterval;
use DateTime;
use CAWL\OnlinePayments\Classes\Services\Checkout\PaymentOptionsService;
use CAWL\OnlinePayments\Classes\Services\PaymentLink\OrderProviderService;
use CAWL\OnlinePayments\Classes\Services\PrestaShop\CancelService;
use CAWL\OnlinePayments\Classes\Services\PrestaShop\OrderService;
use CAWL\OnlinePayments\Classes\Services\PrestaShop\RefundService;
use CAWL\OnlinePayments\Classes\Utility\Installer;
use CAWL\OnlinePayments\Classes\Utility\SessionService;
use CAWL\OnlinePayments\Classes\Utility\Url;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use CAWL\OnlinePayments\Core\Branding\Brand\ActiveBrandProvider;
use CAWL\OnlinePayments\Core\Branding\Brand\ActiveBrandProviderInterface;
use CAWL\OnlinePayments\Core\Branding\Brand\BrandConfig;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\Repositories\PaymentTransactionRepositoryInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentLinks\PaymentLinkRequest;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ProductTypes\ProductType;
use CAWL\OnlinePayments\Core\Infrastructure\Logger\Logger;
use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
use CAWL\OnlinePayments\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use CAWL\OnlinePayments\Core\Infrastructure\Utility\TimeProvider;
use Order;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShopDatabaseException;
use PrestaShopException;
/**
 * Class OnlinePaymentsModule
 *
 * @package OnlinePayments\Classes
 */
class OnlinePaymentsModule extends \PaymentModule
{
    public function install()
    {
        try {
            $success = parent::install();
            $success && $this->getInstaller()->install();
        } catch (\Exception $e) {
            \CAWL\OnlinePayments\Core\Infrastructure\Logger\Logger::logError('Failed to complete installation because ' . $e->getMessage());
            return \false;
        }
        return $success;
    }
    /**
     * Creates plugin installer.
     *
     * @return Installer
     */
    public function getInstaller() : Installer
    {
        return new Installer($this);
    }
    /**
     * @return bool
     */
    public function uninstall() : bool
    {
        try {
            $success = parent::uninstall();
            $success && $this->getInstaller()->uninstall();
            return $success;
        } catch (\Throwable $e) {
            $this->_errors[] = $e->getMessage();
            \PrestaShopLogger::addLog('Online Payments plugin uninstallation failed. Error: ' . $e->getMessage() . ' . Trace: ' . $e->getTraceAsString(), \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR);
            return \false;
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
        $wakeupService = ServiceRegister::getService(TaskRunnerWakeup::class);
        $wakeupService->wakeup();
        $this->loadStyles();
        $this->loadScripts();
        $brand = $this->getBrand();
        $this->context->smarty->assign(['module' => $this->name, 'urls' => $this->getUrls(), 'translations' => $this->getTranslations(), 'brand' => ['name' => $brand->getName(), 'code' => $brand->getCode()]]);
        return $this->display($this->getLocalPath(), 'views/templates/index.tpl');
    }
    private function loadStyles() : void
    {
        $this->context->controller->addCSS([$this->getPathUri() . '/views/css/index.css', $this->getPathUri() . '/views/css/op-admin.css'], 'all', null, \false);
    }
    private function loadScripts() : void
    {
        $this->context->controller->addJS([$this->getPathUri() . '/views/js/index.js'], \false);
    }
    public function getBrand() : BrandConfig
    {
        /** @var ActiveBrandProvider $provider */
        $provider = ServiceRegister::getService(ActiveBrandProviderInterface::class);
        return $provider->getActiveBrand();
    }
    public function getConfig()
    {
        return \json_decode(\file_get_contents(__DIR__ . '/../config.json'), \true);
    }
    /**
     * @param array $params
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionOrderGridDefinitionModifier(array $params) : void
    {
        $definition = $params['definition'];
        /** @var \PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection */
        $columns = $definition->getColumns();
        $columnPaymentReference = new \PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DataColumn('online_payments_payment_reference');
        $columnPaymentReference->setName($this->getConfig()['PAYMENT_REFERENCE_PREFIX'] . ' ' . $this->trans($this->l('Payment Reference')))->setOptions(array('field' => 'paymentReference', 'sortable' => \false));
        $columns->addAfter('payment', $columnPaymentReference);
        $definition->setColumns($columns);
    }
    /**
     * @param array $params
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionOrderGridPresenterModifier(array $params) : void
    {
        $records = $params['presented_grid']['data']['records']->all();
        $transactionRepository = ServiceRegister::getService(PaymentTransactionRepositoryInterface::class);
        foreach ($records as &$record) {
            if ((new Order((int) $record['id_order']))->module !== $this->name) {
                $record['paymentReference'] = '--';
                continue;
            }
            $order = new Order((int) $record['id_order']);
            $transaction = StoreContext::doWithStore($order->id_shop, function () use($transactionRepository, $order) {
                return $transactionRepository->getByMerchantReference($order->id_cart);
            });
            if (empty($transaction)) {
                $record['paymentReference'] = '--';
                continue;
            }
            $record['paymentReference'] = (string) $transaction->getPaymentId();
        }
        $params['presented_grid']['data']['records'] = new \PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection($records);
    }
    private function getUrls() : array
    {
        return ['connection' => ['getSettingsUrl' => Url::getAdminUrl('Connection', 'getConnectionSettings', '{storeId}'), 'submitUrl' => Url::getAdminUrl('Connection', 'connect', '{storeId}'), 'webhooksUrl' => Url::getFrontUrl('webhook') . '?storeId={storeId}'], 'stores' => ['storesUrl' => Url::getAdminUrl('Stores', 'getStores', '{storeId}'), 'currentStoreUrl' => Url::getAdminUrl('Stores', 'getCurrentStore')], 'integration' => ['stateUrl' => Url::getAdminUrl('State', 'index', '{storeId}')], 'version' => ['versionUrl' => Url::getAdminUrl('Version', 'getVersion')], 'payments' => ['getAvailablePaymentsUrl' => Url::getAdminUrl('Payments', 'list', '{storeId}'), 'enableMethodUrl' => Url::getAdminUrl('Payments', 'enable', '{storeId}'), 'saveMethodConfigurationUrl' => Url::getAdminUrl('Payments', 'save', '{storeId}', '{methodId}'), 'getMethodConfigurationUrl' => Url::getAdminUrl('Payments', 'getPaymentMethod', '{storeId}', '{methodId}'), 'getLanguagesUrl' => Url::getAdminUrl('Language', 'getLanguages', '{storeId}')], 'settings' => ['getGeneralSettingsUrl' => Url::getAdminUrl('GeneralSettings', 'getGeneralSettings', '{storeId}'), 'getPaymentStatusesUrl' => Url::getAdminUrl('OrderStatuses', 'getOrderStatuses', '{storeId}'), 'saveConnectionUrl' => Url::getAdminUrl('Connection', 'connect', '{storeId}'), 'saveCardsSettingsUrl' => Url::getAdminUrl('GeneralSettings', 'saveCardsSettings', '{storeId}'), 'savePaymentSettingsUrl' => Url::getAdminUrl('GeneralSettings', 'savePaymentSettings', '{storeId}'), 'saveLogSettingsUrl' => Url::getAdminUrl('GeneralSettings', 'saveLogSettings', '{storeId}'), 'savePayByLinkSettingsUrl' => Url::getAdminUrl('GeneralSettings', 'savePayByLinkSettings', '{storeId}'), 'webhooksUrl' => Url::getFrontUrl('webhook') . '?storeId={storeId}', 'disconnectUrl' => Url::getAdminUrl('GeneralSettings', 'disconnect', '{storeId}')], 'monitoring' => ['getMonitoringLogsUrl' => Url::getAdminUrl('Monitoring', 'getMonitoringLogs', '{storeId}'), 'getWebhookLogsUrl' => Url::getAdminUrl('Monitoring', 'getWebhookLogs', '{storeId}'), 'downloadMonitoringLogsUrl' => Url::getAdminUrl('Monitoring', 'downloadMonitoringLogs', '{storeId}'), 'downloadWebhookLogsUrl' => Url::getAdminUrl('Monitoring', 'downloadWebhookLogs', '{storeId}')]];
    }
    private function getTranslations() : array
    {
        return ['default' => $this->getDefaultTranslations(), 'current' => $this->getCurrentTranslations()];
    }
    private function getDefaultTranslations()
    {
        $baseDir = __DIR__ . '/../views/lang/';
        $translations = \json_decode(\file_get_contents($baseDir . 'en.json'), \true);
        if ($translations) {
            $brand = $this->getBrand()->getCode();
            $config = $this->getConfig();
            $translations[$brand]['links']['configurePlugin'] = $translations[$brand]['links']['configurePlugin'] . 'prestashop';
            $translations['general']['helpLink'] = $config['HELP_LINK'];
        }
        return $translations;
    }
    private function getCurrentTranslations()
    {
        $baseDir = __DIR__ . '/../views/lang/';
        $locale = $this->getLocale();
        $file = \file_exists($baseDir . $locale . '.json') ? $baseDir . $locale . '.json' : $baseDir . 'en.json';
        $translations = \json_decode(\file_get_contents($file), \true);
        if ($translations) {
            $brand = $this->getBrand()->getCode();
            $config = $this->getConfig();
            $translations[$brand]['links']['configurePlugin'] = $translations[$brand]['links']['configurePlugin'] . 'prestashop';
            $translations['general']['helpLink'] = $config['HELP_LINK'];
        }
        return $translations;
    }
    /**
     * @return string
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function getLocale() : string
    {
        $locale = new \Language(\Context::getContext()->employee->id_lang);
        return \in_array($locale->iso_code, ['en', 'de', 'fr', 'nl', 'it', 'es']) ? $locale->iso_code : 'en';
    }
    public function hookActionFrontControllerSetMedia()
    {
        $controller = \Tools::getValue('controller');
        $brand = $this->getBrand();
        switch ($controller) {
            case 'order':
                $this->context->controller->registerJavascript($this->name . '-js-sdk', $brand->getLiveApiEndpoint() . '/hostedtokenization/js/client/tokenizer.min.js', ['server' => 'remote', 'priority' => 1, 'position' => 'head', 'attribute' => 'defer']);
                $this->context->controller->registerStylesheet($this->name . '-css-paymentOptions', $this->getPathUri() . 'views/css/front.css?version=' . $this->version, ['server' => 'remote']);
                $this->context->controller->registerJavascript($this->name . '-js-paymentOptions', $this->getPathUri() . 'views/js/paymentOptions.js?version=' . $this->version, ['position' => 'head', 'priority' => 1000, 'server' => 'remote']);
                break;
            case 'redirect':
                if ($this->name === \Tools::getValue('module', '')) {
                    $this->context->controller->registerJavascript($this->name . '-redirect-javascript', $this->getPathUri() . 'views/js/redirect.js?version=' . $this->version, ['position' => 'bottom', 'priority' => 1000, 'server' => 'remote']);
                }
                break;
        }
    }
    /**
     * @throws \PrestaShopException
     */
    public function hookActionAdminControllerSetMedia()
    {
        if (\Tools::getValue('controller') == 'AdminOrders') {
            \Media::addJsDef(['onlinePaymentsAjaxTransactionUrl' => Url::getAdminUrl('Transaction'), 'onlinePaymentsGenericErrorMessage' => $this->l('An error occurred while processing your request. Please try again.'), 'alertRefund' => $this->l('Do you confirm the refund of the funds?'), 'alertCapture' => $this->l('Do you confirm the capture of the transaction?'), 'alertCancel' => $this->l('Do you confirm the cancellation of the transaction?')]);
            $this->context->controller->addJS([$this->getPathUri() . 'views/js/admin/onlinepayments-payment-link-copy.js', $this->getPathUri() . 'views/js/admin/onlinepayments-backoffice-order-creation.js']);
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
            Logger::logError('Error while presenting payment options', 'PrestaShop.hookPaymentOptions', ['message' => $e->getMessage(), 'type' => \get_class($e), 'trace' => $e->getTraceAsString()]);
        }
        return [];
    }
    /**
     * @return string
     */
    public function hookDisplayPaymentByBinaries()
    {
        $this->context->smarty->assign(['module' => $this->name]);
        return $this->context->smarty->fetch($this->getLocalPath() . '/views/templates/front/hookDisplayPaymentByBinaries.tpl');
    }
    /**
     * @param $params
     *
     * @return string
     */
    public function hookDisplayPaymentTop($params)
    {
        if (\Tools::getValue($this->name . 'DisplayPaymentTopMessage')) {
            $this->context->smarty->assign(['module' => $this->name]);
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
        $this->context->smarty->assign(['module' => $this->name]);
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
        $this->context->smarty->assign(['html' => $html, 'moduleName' => $this->name]);
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
        $order = new \Order((int) $idOrder);
        if (!\Validate::isLoadedObject($order)) {
            throw new \Exception("Module {$this->name}: Cannot load order");
        }
        if ($order->id_shop != $this->context->shop->id || \Shop::getContext() !== \Shop::CONTEXT_SHOP) {
            return $this->displayError(\sprintf($this->l('Please change shop context to shop ID %d'), $order->id_shop));
        }
        try {
            $orderService = new OrderService($this, $this->context->shop->id);
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
        $order = new \Order((int) $invoice->id_order);
        if (!\Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }
        $response = CheckoutAPI::get()->payment($order->id_shop)->getPaymentTransaction($order->id_cart);
        if (!$response->isSuccessful() || null === $response->getPaymentTransaction() || null === $response->getPaymentTransaction()->getPaymentId()) {
            return '';
        }
        $this->context->smarty->assign(['module' => $this->name, 'title' => $this->getBrand()->getName(), 'transaction_id' => $response->getPaymentTransaction()->getPaymentId()->getTransactionId()]);
        return $this->display($this->getLocalPath(), 'views/templates/admin/hookDisplayPDFInvoice.tpl');
    }
    /**
     * @param mixed[] $params
     *
     * @return string
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $idProduct = (string) $params['id_product'];
        $result = AdminAPI::get()->productTypes()->list($idProduct);
        if (!$result->isSuccessful()) {
            return '';
        }
        $this->context->smarty->assign(['availableProductTypes' => [(string) ProductType::foodAndDrink() => $this->l('Food & Drink'), (string) ProductType::homeAndGarden() => $this->l('Home & Garden'), (string) ProductType::giftAndFlowers() => $this->l('Gift & Flowers')], 'module' => $this->name, 'title' => \sprintf($this->l('Please configure this section in case you accept gift cards as payment methods with %s'), $this->getBrand()->getName()), 'selectedProductType' => (string) $result->getSelectedProductType()]);
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
                AdminAPI::get()->productTypes()->save($idProduct, ProductType::parse((string) $form['product_type']));
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
        $shopRefundService = new RefundService($this, $this->context->shop->id);
        $error = $shopRefundService->handleStandard((string) $params['cart']->id, $params['order']);
        if (!empty($error)) {
            \setcookie('error', $error, \time() + 3600, '/');
            \Tools::redirectAdmin($this->generateOrderPageUrl($order));
        }
    }
    public function hookActionOrderStatusUpdate(array $params) : void
    {
        $order = new \Order($params['id_order']);
        $newOrderStatus = $params['newOrderStatus'];
        if ($order->module !== $this->name || (string) $newOrderStatus->id === (string) $order->current_state || (string) $newOrderStatus->id !== (string) \Configuration::getGlobalValue('PS_OS_CANCELED')) {
            return;
        }
        $cartId = \Cart::getCartIdByOrderId($order->id);
        $cancelService = new CancelService($this, $this->context->shop->id);
        $cancelService->handle((string) $cartId, $order);
    }
    /**
     * Hook for displaying header data used in BO.
     *
     * @return false|string Header HTML data as string
     */
    public function hookDisplayBackOfficeHeader() : string
    {
        if (!$this->isEnabled($this->name) || \Tools::getValue('controller') !== 'AdminOrders') {
            return '';
        }
        $generalSettings = AdminAPI::get()->generalSettings($this->context->shop->id)->getGeneralSettings();
        if (!$generalSettings->isSuccessful()) {
            return '';
        }
        $generalSettingsArray = $generalSettings->toArray()['payByLinkSettings'];
        if (!$generalSettingsArray || !$generalSettingsArray['enabled']) {
            return '';
        }
        $expirationDate = TimeProvider::getInstance()->getCurrentLocalTime()->add(new DateInterval('P' . $generalSettingsArray['expirationTime'] . 'D'))->format("Y-m-d");
        $this->context->smarty->assign(['pluginPayByLinkTitle' => $generalSettingsArray['title'], 'pluginExpirationDate' => $expirationDate, 'moduleName' => $this->name]);
        return $this->display($this->getLocalPath(), 'views/templates/hook/onlinepayments-backoffice-order-creation.tpl');
    }
    public function hookActionValidateOrder(array $params) : void
    {
        if (!isset($this->context->controller) || 'admin' !== $this->context->controller->controller_type || $params['order']->module !== $this->name) {
            return;
        }
        $expiresAt = \Tools::getValue("{$this->name}-expires-at-date");
        /** @var \Order $order */
        $order = $params['order'];
        $cartId = \Cart::getCartIdByOrderId($order->id);
        $paymentLinkResponse = AdminAPI::get()->paymentLinks($this->context->shop->id)->create(new PaymentLinkRequest(new OrderProviderService((string) $order->id), $this->context->link->getModuleLink($this->name, 'redirect', ['action' => 'redirectReturnPaymentLink', 'merchantReference' => $cartId]), new DateTime($expiresAt)));
        if ($paymentLinkResponse->isSuccessful()) {
            SessionService::set('successMessage', $this->l('Payment link successfully generated.'));
            return;
        }
        SessionService::set('errorMessage', $this->l('Payment link generation failed. Reason: ') . $paymentLinkResponse->toArray()['errorMessage'] ?? '');
    }
    private function generateOrderPageUrl(\Order $order) : string
    {
        return SymfonyContainer::getInstance()->get('router')->generate('admin_orders_view', ['orderId' => $order->id]);
    }
}
