<?php

namespace {
    use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
    use CAWL\OnlinePayments\Classes\Repositories\PaymentTransactionsRepository;
    use CAWL\OnlinePayments\Classes\Repositories\ProductTypesRepository;
    use CAWL\OnlinePayments\Classes\Repositories\TokensRepository;
    use CAWL\OnlinePayments\Classes\Services\ImageHandler;
    use CAWL\OnlinePayments\Classes\Utility\DatabaseHandler;
    use CAWL\OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
    use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PaymentTransactionEntity;
    use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\ProductTypes\ProductTypeEntity;
    use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\Tokens\TokenEntity;
    use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\LogSettingsRequest;
    use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\PaymentSettingsRequest;
    use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Request\PaymentMethodRequest;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionDetails;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\Credentials;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAction;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Stores\StoreService;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\Cards\FlowType;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\ThreeDSSettings\ExemptionType;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\MethodAdditionalData\ThreeDSSettings\ThreeDSSettings;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductService;
    use CAWL\OnlinePayments\Core\BusinessLogic\Domain\ProductTypes\ProductType;
    use CAWL\OnlinePayments\Core\Infrastructure\Logger\Logger;
    use CAWL\OnlinePayments\Core\Infrastructure\ORM\Entity;
    use CAWL\OnlinePayments\Core\Infrastructure\ORM\Utility\IndexHelper;
    use CAWL\OnlinePayments\Core\Infrastructure\ServiceRegister;
    if (!\defined('_PS_VERSION_')) {
        exit;
    }
    /**
     * Updates module from previous versions to the version 3.0.0
     * Major update that upgrades module to a core library usage
     */
    function upgrade_module_3_0_0(OnlinePaymentsModule $module) : bool
    {
        $previousShopContext = \Shop::getContext();
        $previousShopContextId = null;
        if ($previousShopContext === \Shop::CONTEXT_SHOP) {
            $previousShopContextId = \Shop::getContextShopID();
        }
        if ($previousShopContext === \Shop::CONTEXT_GROUP) {
            $previousShopContextId = \Shop::getContextShopGroupID();
        }
        \Shop::setContext(\Shop::CONTEXT_ALL);
        \initialize_new_plugin_3_0_0($module);
        $shops = \Shop::getShops();
        foreach ($shops as $shop) {
            \Shop::setContext(\Shop::CONTEXT_SHOP, (int) $shop['id_shop']);
            try {
                StoreContext::doWithStore((string) $shop['id_shop'], function () use($shop, $module) {
                    \upgrade_for_shop_3_0_0($module, (int) $shop['id_shop']);
                });
            } catch (\Throwable $e) {
                Logger::logError("Failed to upgrade shop data for shop #{$shop['id_shop']}", "[{$module->name}]_upgrade_module_3_0_0", ['message' => $e->getMessage(), 'type' => \get_class($e), 'trace' => $e->getTraceAsString()]);
            }
        }
        \migratePaymentTransactions($module);
        \migrateStoredTokens($module);
        \migrateProductTypes($module);
        DatabaseHandler::dropTable($module->name . '_hosted_checkout');
        DatabaseHandler::dropTable($module->name . '_transaction');
        DatabaseHandler::dropTable($module->name . '_created_payment');
        DatabaseHandler::dropTable($module->name . '_token');
        DatabaseHandler::dropTable($module->name . '_product_gift_card');
        $module->enable(\true);
        \Shop::setContext($previousShopContext, $previousShopContextId);
        return \true;
    }
    function initialize_new_plugin_3_0_0(OnlinePaymentsModule $module) : void
    {
        try {
            $module->getInstaller()->removeControllers();
            $module->getInstaller()->removeHooks();
            \removeOldVersionLeftoverFiles($module);
            $module->getInstaller()->install();
        } catch (\Throwable $e) {
            Logger::logError('Failed to initialize new plugin', "[{$module->name}]_upgrade_module_3_0_0", ['message' => $e->getMessage(), 'type' => \get_class($e), 'trace' => $e->getTraceAsString()]);
        }
    }
    function upgrade_for_shop_3_0_0(OnlinePaymentsModule $module, int $idShop) : void
    {
        \migrateAccountSettings($module, $idShop);
        \migrateAdvancedSettings($module, $idShop);
        \migratePaymentMethodsSettings($module, $idShop);
    }
    function migrateAccountSettings(OnlinePaymentsModule $module, int $idShop) : void
    {
        $accountSettings = \json_decode(\Configuration::get(\strtoupper($module->name) . '_ACCOUNT_SETTINGS', null, null, $idShop), \true);
        if (!$accountSettings || empty($accountSettings['environment'])) {
            return;
        }
        $mode = ConnectionMode::parse($accountSettings['environment'] === 'prod' ? 'live' : 'test');
        $liveCredentials = null;
        $testCredentials = null;
        if (!empty($accountSettings['prodPspid']) && !empty($accountSettings['prodApiKey']) && !empty($accountSettings['prodApiSecret']) && !empty($accountSettings['prodWebhooksKey']) && !empty($accountSettings['prodWebhooksSecret'])) {
            $liveCredentials = new Credentials($accountSettings['prodPspid'], $accountSettings['prodApiKey'], $accountSettings['prodApiSecret'], $accountSettings['prodWebhooksKey'], $accountSettings['prodWebhooksSecret']);
        }
        if (!empty($accountSettings['testPspid']) && !empty($accountSettings['testApiKey']) && !empty($accountSettings['testApiSecret']) && !empty($accountSettings['testWebhooksKey']) && !empty($accountSettings['testWebhooksSecret'])) {
            $testCredentials = new Credentials($accountSettings['testPspid'], $accountSettings['testApiKey'], $accountSettings['testApiSecret'], $accountSettings['testWebhooksKey'], $accountSettings['testWebhooksSecret']);
        }
        /** @var ConnectionConfigRepositoryInterface $connectionConfigRepository */
        $connectionConfigRepository = ServiceRegister::getService(ConnectionConfigRepositoryInterface::class);
        $connectionConfigRepository->saveConnection(new ConnectionDetails($mode, $liveCredentials, $testCredentials));
        ImageHandler::copyHostedCheckoutDefaultImage($module->getLocalPath() . 'views/assets/images/payment_products/' . PaymentProductId::HOSTED_CHECKOUT . '.svg', (string) $idShop, (string) $mode);
    }
    function migrateAdvancedSettings(OnlinePaymentsModule $module, int $idShop) : void
    {
        $paymentMethodsSettings = \json_decode(\Configuration::get(\strtoupper($module->name) . '_PAYMENT_METHODS_SETTINGS', null, null, $idShop), \true);
        $paymentMethodsSettings = \is_array($paymentMethodsSettings) ? $paymentMethodsSettings : [];
        $advancedSettings = \json_decode(\Configuration::get(\strtoupper($module->name) . '_ADVANCED_SETTINGS', null, null, $idShop), \true);
        if (!$advancedSettings) {
            return;
        }
        /** @var StoreService $storeService */
        $storeService = ServiceRegister::getService(StoreService::class);
        $defaultMapping = $storeService->getDefaultOrderStatusMapping();
        $paymentSettings = \array_key_exists('paymentSettings', $advancedSettings) ? $advancedSettings['paymentSettings'] : [];
        $defaultPaymentSettings = new PaymentSettings();
        try {
            $paymentActionType = PaymentAction::fromState((string) $paymentSettings['transactionType']);
        } catch (\Throwable $e) {
            $paymentActionType = $defaultPaymentSettings->getPaymentAction();
        }
        $autocapture = $defaultPaymentSettings->getAutomaticCapture()->getValue();
        if (\array_key_exists('captureDelay', $paymentSettings) && (int) $paymentSettings['captureDelay'] > 0) {
            $autocapture = \min(5, (int) $paymentSettings['captureDelay']);
            $autocapture = 2 < $autocapture && $autocapture < 5 ? 5 : $autocapture;
            // Convert days to minutes
            $autocapture *= 1440;
        }
        $defaultTemplateName = '';
        if ($paymentMethodsSettings && !empty($paymentMethodsSettings['redirectTemplateFilename'])) {
            $defaultTemplateName = $paymentMethodsSettings['redirectTemplateFilename'];
        }
        AdminAPI::get()->generalSettings($idShop)->savePaymentSettings(new PaymentSettingsRequest($paymentActionType->getType(), $autocapture, $defaultPaymentSettings->getPaymentAttemptsNumber()->getPaymentAttemptsNumber(), \array_key_exists('surchargingEnabled', $advancedSettings) ? (bool) $advancedSettings['surchargingEnabled'] : $defaultPaymentSettings->isApplySurcharge(), \array_key_exists('successOrderStateId', $paymentSettings) ? (string) $paymentSettings['successOrderStateId'] : $defaultMapping->getPaymentCapturedStatus(), \array_key_exists('errorOrderStateId', $paymentSettings) ? (string) $paymentSettings['errorOrderStateId'] : $defaultMapping->getPaymentErrorStatus(), \array_key_exists('pendingOrderStateId', $paymentSettings) ? (string) $paymentSettings['pendingOrderStateId'] : $defaultMapping->getPaymentPendingStatus(), $defaultMapping->getPaymentAuthorizedStatus(), $defaultMapping->getPaymentCancelledStatus(), $defaultMapping->getPaymentRefundedStatus(), $defaultTemplateName));
        AdminAPI::get()->generalSettings($idShop)->saveLogSettings(new LogSettingsRequest(\array_key_exists('logsEnabled', $advancedSettings) ? (bool) $advancedSettings['logsEnabled'] : \false, 10));
    }
    function migratePaymentMethodsSettings(OnlinePaymentsModule $module, int $idShop) : void
    {
        $paymentMethodsSettings = \json_decode(\Configuration::get(\strtoupper($module->name) . '_PAYMENT_METHODS_SETTINGS', null, null, $idShop), \true);
        $advancedSettings = \json_decode(\Configuration::get(\strtoupper($module->name) . '_ADVANCED_SETTINGS', null, null, $idShop), \true);
        $advancedSettings = \is_array($advancedSettings) ? $advancedSettings : [];
        $groupCardPaymentOptions = \array_key_exists('groupCardPaymentOptions', $advancedSettings) ? (bool) $advancedSettings['groupCardPaymentOptions'] : \true;
        if (!$paymentMethodsSettings) {
            return;
        }
        $cardsMethod = AdminAPI::get()->payment($idShop)->getPaymentMethod(PaymentProductId::cards()->getId())->toArray();
        $names = [];
        foreach ($cardsMethod['name'] as $translation) {
            $names[$translation['locale']] = $translation['value'];
        }
        $cardsMethod['name'] = $names;
        $names = [];
        foreach ($cardsMethod['additionalData']['vaultTitleCollection'] as $translation) {
            $names[$translation['locale']] = $translation['value'];
        }
        $cardsMethod['additionalData']['vaultTitleCollection'] = $names;
        if (\array_key_exists('displayIframePaymentOptions', $paymentMethodsSettings) && $paymentMethodsSettings['displayIframePaymentOptions']) {
            $cardsMethod['enabled'] = \true;
        }
        if (!empty($paymentMethodsSettings['iframeCallToAction'])) {
            foreach ($paymentMethodsSettings['iframeCallToAction'] as $lang => $label) {
                $cardsMethod['name'][\strtoupper($lang)] = $label;
            }
        }
        if (!empty($paymentMethodsSettings['iframeTemplateFilename'])) {
            $hostedCheckoutMethod['template'] = $paymentMethodsSettings['iframeTemplateFilename'];
        }
        /** @var PaymentProductService $paymentProductService */
        $paymentProductService = ServiceRegister::getService(PaymentProductService::class);
        $groupCreditCards = \true;
        $creditCardsFlowType = FlowType::iframe()->getType();
        if (!$cardsMethod['enabled'] && \array_key_exists('redirectPaymentMethods', $paymentMethodsSettings)) {
            $supportedPaymentMethods = $paymentProductService->getSupportedPaymentMethods(\true);
            foreach ($paymentMethodsSettings['redirectPaymentMethods'] as $paymentMethodConfig) {
                if (\in_array((string) $paymentMethodConfig['productId'], $supportedPaymentMethods, \true) && PaymentProductId::parse((string) $paymentMethodConfig['productId'])->isCardType() && \array_key_exists('enabled', $paymentMethodConfig) && $paymentMethodConfig['enabled']) {
                    $cardsMethod['enabled'] = \true;
                    $groupCreditCards = \false;
                    $creditCardsFlowType = FlowType::redirect()->getType();
                    break;
                }
            }
        }
        $paymentSettings = \array_key_exists('paymentSettings', $advancedSettings) ? $advancedSettings['paymentSettings'] : [];
        $defaultPaymentSettings = new PaymentSettings();
        try {
            $paymentActionType = PaymentAction::fromState((string) $paymentSettings['transactionType']);
        } catch (\Throwable $e) {
            $paymentActionType = $defaultPaymentSettings->getPaymentAction();
        }
        $defaultThreeDSSettings = new ThreeDSSettings();
        try {
            $exemptionType = ExemptionType::fromState((string) $advancedSettings['threeDSExemptedType']);
        } catch (\Throwable $e) {
            $exemptionType = $defaultThreeDSSettings->getExemptionType();
        }
        $exemptionLimit = $defaultThreeDSSettings->getExemptionLimit()->getPriceInCurrencyUnits();
        if (!empty($advancedSettings['threeDSExemptedValue'])) {
            $exemptionLimit = $advancedSettings['threeDSExemptedValue'];
        }
        AdminAPI::get()->payment($idShop)->save(new PaymentMethodRequest(PaymentProductId::cards()->getId(), $cardsMethod['name'], $cardsMethod['enabled'], $cardsMethod['template'], $paymentActionType->getType(), $cardsMethod['additionalData']['vaultTitleCollection'], null, $groupCreditCards, null, null, null, null, null, null, \array_key_exists('force3DsV2', $advancedSettings) ? (bool) $advancedSettings['force3DsV2'] : $defaultThreeDSSettings->isEnable3ds(), \array_key_exists('enforce3DS', $advancedSettings) ? (bool) $advancedSettings['enforce3DS'] : $defaultThreeDSSettings->isEnforceStrongAuthentication(), \array_key_exists('threeDSExempted', $advancedSettings) ? (bool) $advancedSettings['threeDSExempted'] : $defaultThreeDSSettings->isEnable3dsExemption(), $exemptionType->getType(), $exemptionLimit, $creditCardsFlowType));
        $hostedCheckoutMethod = AdminAPI::get()->payment($idShop)->getPaymentMethod(PaymentProductId::hostedCheckout()->getId())->toArray();
        $names = [];
        foreach ($hostedCheckoutMethod['name'] as $translation) {
            $names[$translation['locale']] = $translation['value'];
        }
        $hostedCheckoutMethod['name'] = $names;
        if (\array_key_exists('displayGenericOption', $paymentMethodsSettings) && $paymentMethodsSettings['displayGenericOption']) {
            $hostedCheckoutMethod['enabled'] = \true;
        }
        if (!empty($paymentMethodsSettings['redirectCallToAction'])) {
            foreach ($paymentMethodsSettings['redirectCallToAction'] as $lang => $label) {
                $hostedCheckoutMethod['name'][\strtoupper($lang)] = $label;
            }
        }
        if (!empty($paymentMethodsSettings['redirectTemplateFilename'])) {
            $hostedCheckoutMethod['template'] = $paymentMethodsSettings['redirectTemplateFilename'];
        }
        $genericLogo = null;
        $accountSettings = \json_decode(\Configuration::get(\strtoupper($module->name) . '_ACCOUNT_SETTINGS', null, null, $idShop), \true);
        if (!empty($paymentMethodsSettings['genericLogoFilename']) && $accountSettings && !empty($accountSettings['environment'])) {
            $mode = $accountSettings['environment'] === 'prod' ? 'live' : 'test';
            $logoFilePath = $module->getLocalPath() . 'views/img/payment_logos/' . $paymentMethodsSettings['genericLogoFilename'];
            if (\file_exists($logoFilePath)) {
                list($width, $height, $logoFileType) = \getimagesize($logoFilePath);
                if (\in_array($logoFileType, ImageHandler::AUTHORIZED_LOGO_EXTENSION)) {
                    ImageHandler::copyHostedCheckoutDefaultImage($logoFilePath, (string) $idShop, $accountSettings['environment'] === 'prod' ? 'live' : 'test', \array_search($logoFileType, ImageHandler::AUTHORIZED_LOGO_EXTENSION));
                    $genericLogo = ImageHandler::getImageUrl((string) PaymentProductId::hostedCheckout(), (string) $idShop, $mode);
                }
            }
        }
        AdminAPI::get()->payment($idShop)->save(new PaymentMethodRequest(PaymentProductId::hostedCheckout()->getId(), $hostedCheckoutMethod['name'], $hostedCheckoutMethod['enabled'], $hostedCheckoutMethod['template'], $paymentActionType->getType(), [], $genericLogo, $groupCardPaymentOptions, null, null, null, null, null, null, \array_key_exists('force3DsV2', $advancedSettings) ? (bool) $advancedSettings['force3DsV2'] : $defaultThreeDSSettings->isEnable3ds(), \array_key_exists('enforce3DS', $advancedSettings) ? (bool) $advancedSettings['enforce3DS'] : $defaultThreeDSSettings->isEnforceStrongAuthentication(), \array_key_exists('threeDSExempted', $advancedSettings) ? (bool) $advancedSettings['threeDSExempted'] : $defaultThreeDSSettings->isEnable3dsExemption(), $exemptionType->getType(), $exemptionLimit));
        if (\array_key_exists('redirectPaymentMethods', $paymentMethodsSettings)) {
            $supportedPaymentMethods = $paymentProductService->getSupportedPaymentMethods(\false);
            foreach ($paymentMethodsSettings['redirectPaymentMethods'] as $paymentMethodConfig) {
                if (!\in_array((string) $paymentMethodConfig['productId'], $supportedPaymentMethods, \true)) {
                    continue;
                }
                $paymentMethod = AdminAPI::get()->payment($idShop)->getPaymentMethod((string) $paymentMethodConfig['productId'])->toArray();
                $names = [];
                foreach ($paymentMethod['name'] as $translation) {
                    $names[$translation['locale']] = $translation['value'];
                }
                $paymentMethod['name'] = $names;
                if (\array_key_exists('enabled', $paymentMethodConfig) && $paymentMethodConfig['enabled']) {
                    $paymentMethod['enabled'] = \true;
                }
                $paymentProductId = PaymentProductId::parse((string) $paymentMethodConfig['productId']);
                $paymentMethodRequest = new PaymentMethodRequest(
                    (string) $paymentProductId,
                    $paymentMethod['name'],
                    $paymentMethod['enabled'],
                    $hostedCheckoutMethod['template'],
                    // Reuse hosted checkout template for all redirect payments
                    $paymentProductId->isSeparateCaptureSupported() ? $paymentActionType->getType() : PaymentAction::authorizeCapture()->getType()
                );
                if (PaymentProductId::googlePay()->equals((string) $paymentMethodConfig['productId'])) {
                    $paymentMethodRequest = new PaymentMethodRequest(
                        (string) $paymentProductId,
                        $paymentMethod['name'],
                        $paymentMethod['enabled'],
                        $hostedCheckoutMethod['template'],
                        // Reuse hosted checkout template for all redirect payments
                        $paymentProductId->isSeparateCaptureSupported() ? $paymentActionType->getType() : PaymentAction::authorizeCapture()->getType(),
                        [],
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        \array_key_exists('force3DsV2', $advancedSettings) ? (bool) $advancedSettings['force3DsV2'] : $defaultThreeDSSettings->isEnable3ds(),
                        \array_key_exists('enforce3DS', $advancedSettings) ? (bool) $advancedSettings['enforce3DS'] : $defaultThreeDSSettings->isEnforceStrongAuthentication(),
                        \array_key_exists('threeDSExempted', $advancedSettings) ? (bool) $advancedSettings['threeDSExempted'] : $defaultThreeDSSettings->isEnable3dsExemption(),
                        $exemptionType->getType(),
                        $exemptionLimit
                    );
                }
                AdminAPI::get()->payment($idShop)->save($paymentMethodRequest);
            }
        }
    }
    function migratePaymentTransactions(OnlinePaymentsModule $module) : void
    {
        $newPaymentTransactionTable = PaymentTransactionsRepository::getFullTableName();
        $automaticCapturePerShop = [];
        $offset = 0;
        $dbQuery = new \DbQuery();
        $dbQuery->select('cart.id_shop, cart.id_customer, tnx.date_add as tnx_date_add, payment.*')->from($module->name . '_created_payment', 'payment')->leftJoin('cart', 'cart', 'payment.id_cart = cart.id_cart')->leftJoin($module->name . '_transaction', 'tnx', 'payment.payment_id = tnx.reference')->limit(100, $offset);
        $payments = \Db::getInstance()->executeS($dbQuery);
        while (!empty($payments)) {
            \Db::getInstance()->insert($newPaymentTransactionTable, \array_map(function (array $payment) use($automaticCapturePerShop) {
                if (!\array_key_exists($payment['id_shop'], $automaticCapturePerShop)) {
                    $automaticCapturePerShop[$payment['id_shop']] = 0;
                    $settingsResponse = AdminAPI::get()->generalSettings((string) $payment['id_shop'])->getGeneralSettings();
                    if ($settingsResponse->isSuccessful()) {
                        $automaticCapturePerShop[$payment['id_shop']] = $settingsResponse->toArray()['paymentSettings']['automaticCapture'];
                    }
                }
                return \mapPaymentsToEntityRow($payment, (int) $automaticCapturePerShop[$payment['id_shop']]);
            }, $payments));
            $offset += 100;
            $dbQuery->limit(100, $offset);
            $payments = \Db::getInstance()->executeS($dbQuery);
        }
        $offset = 0;
        $dbQuery = new \DbQuery();
        $dbQuery->select('cart.id_shop, cart.id_customer, tnx.date_add as tnx_date_add, hc.*')->from($module->name . '_hosted_checkout', 'hc')->leftJoin('cart', 'cart', 'hc.id_cart = cart.id_cart')->leftJoin($module->name . '_transaction', 'tnx', 'concat(hc.session_id, "_0") = tnx.reference')->limit(100, $offset);
        $hostedCheckouts = \Db::getInstance()->executeS($dbQuery);
        while (!empty($hostedCheckouts)) {
            \Db::getInstance()->insert($newPaymentTransactionTable, \array_map(function (array $hostedCheckout) use($automaticCapturePerShop) {
                if (!\array_key_exists($hostedCheckout['id_shop'], $automaticCapturePerShop)) {
                    $automaticCapturePerShop[$hostedCheckout['id_shop']] = 0;
                    $settingsResponse = AdminAPI::get()->generalSettings((string) $hostedCheckout['id_shop'])->getGeneralSettings();
                    if ($settingsResponse->isSuccessful()) {
                        $automaticCapturePerShop[$hostedCheckout['id_shop']] = $settingsResponse->toArray()['paymentSettings']['automaticCapture'];
                    }
                }
                return \mapHostedCheckoutToEntityRow($hostedCheckout, (int) $automaticCapturePerShop[$hostedCheckout['id_shop']]);
            }, $hostedCheckouts));
            $offset += 100;
            $dbQuery->limit(100, $offset);
            $hostedCheckouts = \Db::getInstance()->executeS($dbQuery);
        }
    }
    function migrateStoredTokens(OnlinePaymentsModule $module) : void
    {
        $newTokensTable = TokensRepository::getFullTableName();
        $offset = 0;
        $dbQuery = new \DbQuery();
        $dbQuery->select('*')->from($module->name . '_token', 'tokens')->limit(100, $offset);
        $tokens = \Db::getInstance()->executeS($dbQuery);
        while (!empty($tokens)) {
            \Db::getInstance()->insert($newTokensTable, \array_map('mapTokensToEntityRow', $tokens));
            $offset += 100;
            $dbQuery->limit(100, $offset);
            $tokens = \Db::getInstance()->executeS($dbQuery);
        }
    }
    function migrateProductTypes(OnlinePaymentsModule $module) : void
    {
        $newPaymentTypeTable = ProductTypesRepository::getFullTableName();
        $offset = 0;
        $dbQuery = new \DbQuery();
        $dbQuery->select('*')->from($module->name . '_product_gift_card', 'pt')->limit(100, $offset);
        $productTypes = \Db::getInstance()->executeS($dbQuery);
        while (!empty($productTypes)) {
            \Db::getInstance()->insert($newPaymentTypeTable, \array_map('mapProductTypesToEntityRow', $productTypes));
            $offset += 100;
            $dbQuery->limit(100, $offset);
            $productTypes = \Db::getInstance()->executeS($dbQuery);
        }
    }
    function mapPaymentsToEntityRow(array $payment, int $automaticCapture) : array
    {
        $statusToStatusIdMap = ["CREATED" => '0', "CANCELLED" => '1', "REJECTED" => '2', "REJECTED_CAPTURE" => '93', "REDIRECTED" => '46', "PENDING_PAYMENT" => '51', "PENDING_COMPLETION" => '51', "PENDING_CAPTURE" => '5', "AUTHORIZATION_REQUESTED" => '51', "CAPTURE_REQUESTED" => '91', "CAPTURED" => '9', "REVERSED" => '81', "REFUND_REQUESTED" => '71', "REFUNDED" => '8'];
        $statusCode = null;
        if (\array_key_exists($payment['status'], $statusToStatusIdMap)) {
            $statusCode = StatusCode::parse($statusToStatusIdMap[$payment['status']]);
        }
        $captureTime = null;
        $dateAdd = \DateTime::createFromFormat('Y-m-d H:i:s', (string) $payment['date_add']);
        $txnDateAdd = \DateTime::createFromFormat('Y-m-d H:i:s', (string) $payment['tnx_date_add']);
        $now = new \DateTime();
        if ($statusCode->equals(StatusCode::authorized()) && $automaticCapture > 0 && $dateAdd && $txnDateAdd && $txnDateAdd->diff($now)->format('%a') < 32) {
            $captureTime = $txnDateAdd->add(new \DateInterval("PT{$automaticCapture}M"));
            // If capture time already elapsed auto-capture is already finished
            if ($captureTime->getTimestamp() < $now->getTimestamp()) {
                $captureTime = null;
            }
        }
        $entity = new PaymentTransactionEntity();
        $entity->setStoreId((string) $payment['id_shop']);
        $entity->setPaymentTransaction(new PaymentTransaction((string) $payment['id_cart'], PaymentId::parse($payment['payment_id']), !empty($payment['returnmac']) ? $payment['returnmac'] : null, $statusCode, !empty($payment['id_customer']) ? $payment['id_customer'] : null, $dateAdd ?? null, $dateAdd ?? null, null, null, $captureTime));
        return \prepareDataForInsertOrUpdate($entity);
    }
    function mapHostedCheckoutToEntityRow(array $hostedCheckout, int $automaticCapture) : array
    {
        $txnDateAdd = \DateTime::createFromFormat('Y-m-d H:i:s', (string) $hostedCheckout['tnx_date_add']);
        $statusCode = StatusCode::incomplete();
        if ($txnDateAdd) {
            $statusCode = $automaticCapture > 0 ? StatusCode::authorized() : StatusCode::completed();
        }
        $captureTime = null;
        $dateAdd = \DateTime::createFromFormat('Y-m-d H:i:s', (string) $hostedCheckout['date_add']);
        $now = new \DateTime();
        if ($statusCode->equals(StatusCode::authorized()) && $automaticCapture > 0 && $dateAdd && $txnDateAdd && $txnDateAdd->diff($now)->format('%a') < 32) {
            $captureTime = $txnDateAdd->add(new \DateInterval("PT{$automaticCapture}M"));
            // If capture time already elapsed auto-capture is already finished
            if ($captureTime->getTimestamp() < $now->getTimestamp()) {
                $captureTime = null;
                $statusCode = StatusCode::completed();
            }
        }
        $entity = new PaymentTransactionEntity();
        $entity->setStoreId((string) $hostedCheckout['id_shop']);
        $entity->setPaymentTransaction(new PaymentTransaction((string) $hostedCheckout['id_cart'], PaymentId::parse($hostedCheckout['session_id']), !empty($hostedCheckout['returnmac']) ? $hostedCheckout['returnmac'] : null, $statusCode, !empty($hostedCheckout['id_customer']) ? $hostedCheckout['id_customer'] : null, $dateAdd ?? null, $dateAdd ?? null, null, null, $captureTime));
        return \prepareDataForInsertOrUpdate($entity);
    }
    function mapTokensToEntityRow(array $token) : array
    {
        $entity = new TokenEntity();
        $entity->setStoreId((string) $token['id_shop']);
        $entity->setToken(new Token((string) $token['id_customer'], (string) $token['value'], (string) $token['product_id'], (string) $token['card_number'], (string) $token['expiry_date']));
        return \prepareDataForInsertOrUpdate($entity);
    }
    function mapProductTypesToEntityRow(array $productType) : array
    {
        try {
            $type = ProductType::parse((string) $productType['product_type']);
        } catch (\Throwable $e) {
            $type = ProductType::foodAndDrink();
        }
        $entity = new ProductTypeEntity();
        $entity->setProductId((string) $productType['id_product']);
        $entity->setProductType($type);
        return \prepareDataForInsertOrUpdate($entity);
    }
    function prepareDataForInsertOrUpdate(Entity $entity) : array
    {
        $indexes = IndexHelper::transformFieldsToIndexes($entity);
        $record = ['entity_type' => \pSQL($entity->getConfig()->getType()), 'data' => \pSQL(\json_encode($entity->toArray()), \true)];
        foreach ($indexes as $index => $value) {
            $record['index_' . $index] = $value !== null ? \pSQL($value, \true) : null;
        }
        return $record;
    }
    function removeOldVersionLeftoverFiles(OnlinePaymentsModule $module)
    {
        \deleteLeftoverDirectories($module);
        \deleteLeftoverFiles($module);
    }
    function deleteLeftoverDirectories(OnlinePaymentsModule $module)
    {
        $directories = ['config', 'install', 'src', "views/templates/admin/{$module->name}_configuration"];
        foreach ($directories as $directory) {
            \Tools::deleteDirectory($module->getLocalPath() . $directory);
        }
    }
    function deleteLeftoverFiles(OnlinePaymentsModule $module)
    {
        $adminControllerPrefix = \ucfirst($module->name);
        $files = ['classes/CreatedPayment.php', 'classes/HostedCheckout.php', 'classes/WorldlineopCartChecksum.php', 'classes/WorldlineopToken.php', 'classes/WorldlineopTransaction.php', "controllers/admin/Admin{$adminControllerPrefix}AjaxController.php", "controllers/admin/Admin{$adminControllerPrefix}AjaxTransactionController.php", "controllers/admin/Admin{$adminControllerPrefix}ConfigurationController.php", "controllers/admin/Admin{$adminControllerPrefix}LogsController.php", 'controllers/front/croncapture.php', 'controllers/front/cronpending.php', 'controllers/front/rejected.php', 'upgrade/upgrade-1.1.0.php', 'upgrade/upgrade-1.2.0.php', 'upgrade/upgrade-1.3.1.php', 'upgrade/upgrade-1.4.0.php', 'upgrade/upgrade-2.0.2.php', 'views/assets/_advancedSettings.scss', 'views/assets/_header.scss', 'views/assets/_paymentMethodsSettings.scss', 'views/assets/_upload.scss', 'views/assets/config.scss', 'views/assets/front.scss', 'views/assets/storedcards.scss', 'views/css/config.css', 'views/templates/front/rejected.tpl'];
        foreach ($files as $file) {
            \Tools::deleteFile($module->getLocalPath() . $file);
        }
    }
}
