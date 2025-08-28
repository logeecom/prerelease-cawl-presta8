<?php

use OnlinePayments\Classes\OnlinePaymentsModule;
use OnlinePayments\Classes\Repositories\PaymentTransactionsRepository;
use OnlinePayments\Classes\Repositories\ProductTypesRepository;
use OnlinePayments\Classes\Repositories\TokensRepository;
use OnlinePayments\Classes\Utility\DatabaseHandler;
use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use OnlinePayments\Core\Bootstrap\DataAccess\PaymentTransaction\PaymentTransactionEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\ProductTypes\ProductTypeEntity;
use OnlinePayments\Core\Bootstrap\DataAccess\Tokens\TokenEntity;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\CardsSettingsRequest;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\LogSettingsRequest;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\PaymentSettingsRequest;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Request\PaymentMethodRequest;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionDetails;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Credentials;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\ExemptionType;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAction;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\Token;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Stores\StoreService;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentId;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\StatusCode;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Core\BusinessLogic\Domain\ProductTypes\ProductType;
use OnlinePayments\Core\Infrastructure\Logger\Logger;
use OnlinePayments\Core\Infrastructure\ORM\Entity;
use OnlinePayments\Core\Infrastructure\ORM\Utility\IndexHelper;
use OnlinePayments\Core\Infrastructure\ServiceRegister;

if (!defined('_PS_VERSION_')) {
    exit;
}

//require_once rtrim(dirname(dirname(__FILE__), '/')) . '/vendor/autoload.php';
//require_once rtrim(dirname(dirname(__FILE__), '/')) . '/old-vendor/autoload.php';

/**
 * Updates module from previous versions to the version 3.0.0
 * Major update that upgrades module to a core library usage
 */
function upgrade_module_3_0_0(OnlinePaymentsModule $module): bool
{
    $previousShopContext = \Shop::getContext();
    \Shop::setContext(\Shop::CONTEXT_ALL);

    initialize_new_plugin_3_0_0($module);

    $shops = \Shop::getShops();
    foreach ($shops as $shop) {
        \Shop::setContext(\Shop::CONTEXT_SHOP, (int)$shop['id_shop']);

        try {
            StoreContext::doWithStore((string)$shop['id_shop'], function () use ($shop, $module) {
                upgrade_for_shop_3_0_0($module, (int)$shop['id_shop']);
            });
        } catch (\Throwable $e) {
            Logger::logError(
                "Failed to upgrade shop data for shop #{$shop['id_shop']}",
                "[$module->name]_upgrade_module_3_0_0",
                [
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        }
    }

    migratePaymentTransactions($module);
    migrateStoredTokens($module);
    migrateProductTypes($module);

    DatabaseHandler::dropTable($module->name . '_hosted_checkout');
    DatabaseHandler::dropTable($module->name . '_transaction');
    DatabaseHandler::dropTable($module->name . '_created_payment');
    DatabaseHandler::dropTable($module->name . '_token');
    DatabaseHandler::dropTable($module->name . '_product_gift_card');

    $module->enable(true);

    \Shop::setContext($previousShopContext);

    return true;
}

function initialize_new_plugin_3_0_0(OnlinePaymentsModule $module): void
{
    try {
        $module->getInstaller()->removeControllers();
        $module->getInstaller()->removeHooks();
        $module->getInstaller()->install();
    } catch (\Throwable $e) {
        Logger::logError(
            'Failed to initialize new plugin',
            "[$module->name]_upgrade_module_3_0_0",
            [
                'message' => $e->getMessage(),
                'type' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]
        );
    }
}

function upgrade_for_shop_3_0_0(OnlinePaymentsModule $module, int $idShop): void
{
    migrateAccountSettings($module, $idShop);
    migrateAdvancedSettings($module, $idShop);
    migratePaymentMethodsSettings($module, $idShop);
}

function migrateAccountSettings(OnlinePaymentsModule $module, int $idShop): void
{
    $accountSettings = json_decode(
        \Configuration::get(strtoupper($module->name) . '_ACCOUNT_SETTINGS', null, null, $idShop), true
    );

    if (!$accountSettings || empty($accountSettings['environment'])) {
        return;
    }

    $mode = ConnectionMode::parse($accountSettings['environment'] === 'prod' ? 'live' : 'test');
    $liveCredentials = null;
    $testCredentials = null;
    if (
        !empty($accountSettings['prodPspid']) &&
        !empty($accountSettings['prodApiKey']) &&
        !empty($accountSettings['prodApiSecret']) &&
        !empty($accountSettings['prodWebhooksKey']) &&
        !empty($accountSettings['prodWebhooksSecret'])
    ) {
        $liveCredentials = new Credentials(
            $accountSettings['prodPspid'],
            $accountSettings['prodApiKey'],
            $accountSettings['prodApiSecret'],
            $accountSettings['prodWebhooksKey'],
            $accountSettings['prodWebhooksSecret']
        );
    }

    if (
        !empty($accountSettings['testPspid']) &&
        !empty($accountSettings['testApiKey']) &&
        !empty($accountSettings['testApiSecret']) &&
        !empty($accountSettings['testWebhooksKey']) &&
        !empty($accountSettings['testWebhooksSecret'])
    ) {
        $testCredentials = new Credentials(
            $accountSettings['testPspid'],
            $accountSettings['testApiKey'],
            $accountSettings['testApiSecret'],
            $accountSettings['testWebhooksKey'],
            $accountSettings['testWebhooksSecret']
        );
    }

    /** @var ConnectionConfigRepositoryInterface $connectionConfigRepository */
    $connectionConfigRepository = ServiceRegister::getService(ConnectionConfigRepositoryInterface::class);;

    $connectionConfigRepository->saveConnection(new ConnectionDetails(
        $mode,
        $liveCredentials,
        $testCredentials
    ));
}

function migrateAdvancedSettings(OnlinePaymentsModule $module, int $idShop): void
{
    $advancedSettings = json_decode(
        \Configuration::get(strtoupper($module->name) . '_ADVANCED_SETTINGS', null, null, $idShop), true
    );

    if (!$advancedSettings) {
        return;
    }

    $defaultCardsSettings = new CardsSettings();

    try {
        $exemptionType = ExemptionType::fromState((string)$advancedSettings['threeDSExemptedType']);
    } catch (\Throwable $e) {
        $exemptionType = $defaultCardsSettings->getExemptionType();
    }

    $exemptionLimit = $defaultCardsSettings->getExemptionLimit()->getPriceInCurrencyUnits();
    if (!empty($advancedSettings['threeDSExemptedValue'])) {
        $exemptionLimit = $advancedSettings['threeDSExemptedValue'];
    }

    AdminAPI::get()->generalSettings($idShop)->saveCardsSettings(new CardsSettingsRequest(
        array_key_exists('force3DsV2',
            $advancedSettings) ? (bool)$advancedSettings['force3DsV2'] : $defaultCardsSettings->isEnable3ds(),
        array_key_exists('enforce3DS',
            $advancedSettings) ? (bool)$advancedSettings['enforce3DS'] : $defaultCardsSettings->isEnforceStrongAuthentication(),
        array_key_exists('threeDSExempted',
            $advancedSettings) ? (bool)$advancedSettings['threeDSExempted'] : $defaultCardsSettings->isEnable3dsExemption(),
        $exemptionType->getType(),
        $exemptionLimit
    ));

    /** @var StoreService $storeService */
    $storeService = ServiceRegister::getService(StoreService::class);
    $defaultMapping = $storeService->getDefaultOrderStatusMapping();
    $paymentSettings = array_key_exists('paymentSettings',
        $advancedSettings) ? $advancedSettings['paymentSettings'] : [];
    $defaultPaymentSettings = new PaymentSettings();
    try {
        $paymentActionType = PaymentAction::fromState((string)$paymentSettings['transactionType']);
    } catch (\Throwable $e) {
        $paymentActionType = $defaultPaymentSettings->getPaymentAction();
    }

    $autocapture = $defaultPaymentSettings->getAutomaticCapture()->getValue();
    if (array_key_exists('captureDelay', $paymentSettings) && (int)$paymentSettings['captureDelay'] > 0) {
        $autocapture = min(5, (int)$paymentSettings['captureDelay']);
        $autocapture = 2 < $autocapture && $autocapture < 5 ? 5 : $autocapture;
        // Convert days to minutes
        $autocapture *= 1440;
    }

    AdminAPI::get()->generalSettings($idShop)->savePaymentSettings(new PaymentSettingsRequest(
        $paymentActionType->getType(),
        $autocapture,
        $defaultPaymentSettings->getPaymentAttemptsNumber()->getPaymentAttemptsNumber(),
        array_key_exists('surchargingEnabled',
            $paymentSettings) ? (bool)$paymentSettings['surchargingEnabled'] : $defaultPaymentSettings->isApplySurcharge(),
        array_key_exists('successOrderStateId',
            $paymentSettings) ? (string)$paymentSettings['successOrderStateId'] : $defaultMapping->getPaymentCapturedStatus(),
        array_key_exists('errorOrderStateId',
            $paymentSettings) ? (string)$paymentSettings['errorOrderStateId'] : $defaultMapping->getPaymentErrorStatus(),
        array_key_exists('pendingOrderStateId',
            $paymentSettings) ? (string)$paymentSettings['pendingOrderStateId'] : $defaultMapping->getPaymentPendingStatus(),
        $defaultMapping->getPaymentAuthorizedStatus(),
        $defaultMapping->getPaymentCancelledStatus(),
        $defaultMapping->getPaymentRefundedStatus(),
    ));

    AdminAPI::get()->generalSettings($idShop)->saveLogSettings(new LogSettingsRequest(
        array_key_exists('logsEnabled', $paymentSettings) ? (bool)$paymentSettings['logsEnabled'] : false,
        10
    ));
}

function migratePaymentMethodsSettings(OnlinePaymentsModule $module, int $idShop): void
{
    $paymentMethodsSettings = json_decode(
        \Configuration::get(strtoupper($module->name) . '_PAYMENT_METHODS_SETTINGS', null, null, $idShop), true
    );

    $advancedSettings = json_decode(
        \Configuration::get(strtoupper($module->name) . '_ADVANCED_SETTINGS', null, null, $idShop), true
    );

    $groupCardPaymentOptions = array_key_exists('groupCardPaymentOptions',
        $advancedSettings) ? (bool)$advancedSettings['groupCardPaymentOptions'] : true;


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

    if (array_key_exists('displayIframePaymentOptions',
            $paymentMethodsSettings) && $paymentMethodsSettings['displayIframePaymentOptions']) {
        $cardsMethod['enabled'] = true;
    }

    if (!empty($paymentMethodsSettings['iframeCallToAction'])) {
        foreach ($paymentMethodsSettings['iframeCallToAction'] as $lang => $label) {
            $cardsMethod['name'][strtoupper($lang)] = $label;
        }
    }

    if (!empty($paymentMethodsSettings['iframeTemplateFilename'])) {
        $hostedCheckoutMethod['template'] = $paymentMethodsSettings['iframeTemplateFilename'];
    }

    AdminAPI::get()->payment($idShop)->save(new PaymentMethodRequest(
        PaymentProductId::cards()->getId(),
        $cardsMethod['name'],
        $cardsMethod['enabled'],
        $cardsMethod['template'],
        $cardsMethod['additionalData']['vaultTitleCollection']
    ));

    $hostedCheckoutMethod = AdminAPI::get()->payment($idShop)->getPaymentMethod(PaymentProductId::hostedCheckout()->getId())->toArray();
    $names = [];
    foreach ($hostedCheckoutMethod['name'] as $translation) {
        $names[$translation['locale']] = $translation['value'];
    }

    $hostedCheckoutMethod['name'] = $names;

    if (array_key_exists('displayGenericOption',
            $paymentMethodsSettings) && $paymentMethodsSettings['displayGenericOption']) {
        $hostedCheckoutMethod['enabled'] = true;
    }

    if (!empty($paymentMethodsSettings['redirectCallToAction'])) {
        foreach ($paymentMethodsSettings['redirectCallToAction'] as $lang => $label) {
            $hostedCheckoutMethod['name'][strtoupper($lang)] = $label;
        }
    }

    if (!empty($paymentMethodsSettings['redirectTemplateFilename'])) {
        $hostedCheckoutMethod['template'] = $paymentMethodsSettings['redirectTemplateFilename'];
    }

    AdminAPI::get()->payment($idShop)->save(new PaymentMethodRequest(
        PaymentProductId::hostedCheckout()->getId(),
        $hostedCheckoutMethod['name'],
        $hostedCheckoutMethod['enabled'],
        $hostedCheckoutMethod['template'],
        [],
        null,
        $groupCardPaymentOptions
    ));

    if (array_key_exists('redirectPaymentMethods', $paymentMethodsSettings)) {
        foreach ($paymentMethodsSettings['redirectPaymentMethods'] as $paymentMethodConfig) {
            if (!PaymentProductId::isSupported((string)$paymentMethodConfig['productId'])) {
                continue;
            }

            $paymentMethod = AdminAPI::get()->payment($idShop)->getPaymentMethod((string)$paymentMethodConfig['productId'])->toArray();
            $names = [];
            foreach ($paymentMethod['name'] as $translation) {
                $names[$translation['locale']] = $translation['value'];
            }

            $paymentMethod['name'] = $names;

            if (array_key_exists('enabled', $paymentMethodConfig) && $paymentMethodConfig['enabled']) {
                $paymentMethod['enabled'] = true;
            }

            AdminAPI::get()->payment($idShop)->save(new PaymentMethodRequest(
                (string)$paymentMethodConfig['productId'],
                $paymentMethod['name'],
                $paymentMethod['enabled'],
                $hostedCheckoutMethod['template'] // Reuse hosted checkout template for all redirect payments
            ));
        }
    }
}

function migratePaymentTransactions(OnlinePaymentsModule $module): void
{
    $newPaymentTransactionTable = PaymentTransactionsRepository::getFullTableName();
    $automaticCapturePerShop = [];

    $offset = 0;
    $dbQuery = new DbQuery();
    $dbQuery
        ->select('cart.id_shop, cart.id_customer, tnx.date_add as tnx_date_add, payment.*')
        ->from($module->name . '_created_payment', 'payment')
        ->leftJoin('cart', 'cart', 'payment.id_cart = cart.id_cart')
        ->leftJoin($module->name . '_transaction', 'tnx', 'payment.payment_id = tnx.reference')
        ->limit(100, $offset);

    $payments = Db::getInstance()->executeS($dbQuery);
    while (!empty($payments)) {
        Db::getInstance()->insert(
            $newPaymentTransactionTable,
            array_map(function (array $payment) use ($automaticCapturePerShop) {
                if (!array_key_exists($payment['id_shop'], $automaticCapturePerShop)) {
                    $automaticCapturePerShop[$payment['id_shop']] = 0;
                    $settingsResponse = AdminAPI::get()
                        ->generalSettings((string)$payment['id_shop'])
                        ->getGeneralSettings();
                    if ($settingsResponse->isSuccessful()) {
                        $automaticCapturePerShop[$payment['id_shop']] = $settingsResponse->toArray()['paymentSettings']['automaticCapture'];
                    }
                }

                return mapPaymentsToEntityRow($payment, (int)$automaticCapturePerShop[$payment['id_shop']]);
            }, $payments)
        );

        $offset += 100;
        $dbQuery->limit(100, $offset);
        $payments = Db::getInstance()->executeS($dbQuery);
    }

    $offset = 0;
    $dbQuery = new DbQuery();
    $dbQuery
        ->select('cart.id_shop, cart.id_customer, tnx.date_add as tnx_date_add, hc.*')
        ->from($module->name . '_hosted_checkout', 'hc')
        ->leftJoin('cart', 'cart', 'hc.id_cart = cart.id_cart')
        ->leftJoin($module->name . '_transaction', 'tnx', 'concat(hc.session_id, "_0") = tnx.reference')
        ->limit(100, $offset);

    $hostedCheckouts = Db::getInstance()->executeS($dbQuery);
    while (!empty($hostedCheckouts)) {
        Db::getInstance()->insert(
            $newPaymentTransactionTable,
            array_map(function (array $hostedCheckout) use ($automaticCapturePerShop) {
                if (!array_key_exists($hostedCheckout['id_shop'], $automaticCapturePerShop)) {
                    $automaticCapturePerShop[$hostedCheckout['id_shop']] = 0;
                    $settingsResponse = AdminAPI::get()
                        ->generalSettings((string)$hostedCheckout['id_shop'])
                        ->getGeneralSettings();
                    if ($settingsResponse->isSuccessful()) {
                        $automaticCapturePerShop[$hostedCheckout['id_shop']] = $settingsResponse->toArray()['paymentSettings']['automaticCapture'];
                    }
                }

                return mapHostedCheckoutToEntityRow($hostedCheckout, (int)$automaticCapturePerShop[$hostedCheckout['id_shop']]);
            }, $hostedCheckouts)
        );

        $offset += 100;
        $dbQuery->limit(100, $offset);
        $hostedCheckouts = Db::getInstance()->executeS($dbQuery);
    }
}

function migrateStoredTokens(OnlinePaymentsModule $module): void
{
    $newTokensTable = TokensRepository::getFullTableName();

    $offset = 0;
    $dbQuery = new DbQuery();
    $dbQuery
        ->select('*')
        ->from($module->name . '_token', 'tokens')
        ->limit(100, $offset);

    $tokens = Db::getInstance()->executeS($dbQuery);
    while (!empty($tokens)) {
        Db::getInstance()->insert($newTokensTable, array_map('mapTokensToEntityRow', $tokens));

        $offset += 100;
        $dbQuery->limit(100, $offset);
        $tokens = Db::getInstance()->executeS($dbQuery);
    }
}

function migrateProductTypes(OnlinePaymentsModule $module): void
{
    $newPaymentTypeTable = ProductTypesRepository::getFullTableName();

    $offset = 0;
    $dbQuery = new DbQuery();
    $dbQuery
        ->select('*')
        ->from($module->name . '_product_gift_card', 'pt')
        ->limit(100, $offset);

    $productTypes = Db::getInstance()->executeS($dbQuery);
    while (!empty($productTypes)) {
        Db::getInstance()->insert($newPaymentTypeTable, array_map('mapProductTypesToEntityRow', $productTypes));

        $offset += 100;
        $dbQuery->limit(100, $offset);
        $productTypes = Db::getInstance()->executeS($dbQuery);
    }
}

function mapPaymentsToEntityRow(array $payment, int $automaticCapture): array
{
    $statusToStatusIdMap = [
        "CREATED" => '0',
        "CANCELLED" => '1',
        "REJECTED" => '2',
        "REJECTED_CAPTURE" => '93',
        "REDIRECTED" => '46',
        "PENDING_PAYMENT" => '51',
        "PENDING_COMPLETION" => '51',
        "PENDING_CAPTURE" => '5',
        "AUTHORIZATION_REQUESTED" => '51',
        "CAPTURE_REQUESTED" => '91',
        "CAPTURED" => '9',
        "REVERSED" => '81',
        "REFUND_REQUESTED" => '71',
        "REFUNDED" => '8',
    ];;
    $statusCode = null;
    if (array_key_exists($payment['status'], $statusToStatusIdMap)) {
        $statusCode = StatusCode::parse($statusToStatusIdMap[$payment['status']]);
    }

    $captureTime = null;
    $dateAdd = DateTime::createFromFormat('Y-m-d H:i:s', (string)$payment['date_add']);
    $txnDateAdd = DateTime::createFromFormat('Y-m-d H:i:s', (string)$payment['tnx_date_add']);
    $now = new DateTime();
    if (
        $statusCode->equals(StatusCode::authorized()) &&
        $automaticCapture > 0 &&
        $dateAdd && $txnDateAdd && $txnDateAdd->diff($now)->format('%a') < 32
    ) {
        $captureTime = $txnDateAdd->add(new \DateInterval("PT{$automaticCapture}M"));
        // If capture time already elapsed auto-capture is already finished
        if ($captureTime->getTimestamp() < $now->getTimestamp()) {
            $captureTime = null;
        }
    }

    $entity = new PaymentTransactionEntity();
    $entity->setStoreId((string)$payment['id_shop']);
    $entity->setPaymentTransaction(new PaymentTransaction(
        (string)$payment['id_cart'],
        PaymentId::parse($payment['payment_id']),
        !empty($payment['returnmac']) ? $payment['returnmac'] : null,
        $statusCode,
        !empty($payment['id_customer']) ? $payment['id_customer'] : null,
        $dateAdd ?? null,
        $dateAdd ?? null,
        null,
        null,
        $captureTime
    ));

    return prepareDataForInsertOrUpdate($entity);
}

function mapHostedCheckoutToEntityRow(array $hostedCheckout, int $automaticCapture): array
{
    $txnDateAdd = DateTime::createFromFormat('Y-m-d H:i:s', (string)$hostedCheckout['tnx_date_add']);

    $statusCode = StatusCode::incomplete();
    if ($txnDateAdd) {
        $statusCode = $automaticCapture > 0 ? StatusCode::authorized() : StatusCode::completed();
    }

    $captureTime = null;
    $dateAdd = DateTime::createFromFormat('Y-m-d H:i:s', (string)$hostedCheckout['date_add']);
    $now = new DateTime();
    if (
        $statusCode->equals(StatusCode::authorized()) &&
        $automaticCapture > 0 &&
        $dateAdd && $txnDateAdd && $txnDateAdd->diff($now)->format('%a') < 32
    ) {
        $captureTime = $txnDateAdd->add(new \DateInterval("PT{$automaticCapture}M"));
        // If capture time already elapsed auto-capture is already finished
        if ($captureTime->getTimestamp() < $now->getTimestamp()) {
            $captureTime = null;
            $statusCode = StatusCode::completed();
        }
    }

    $entity = new PaymentTransactionEntity();
    $entity->setStoreId((string)$hostedCheckout['id_shop']);
    $entity->setPaymentTransaction(new PaymentTransaction(
        (string)$hostedCheckout['id_cart'],
        PaymentId::parse($hostedCheckout['session_id']),
        !empty($hostedCheckout['returnmac']) ? $hostedCheckout['returnmac'] : null,
        $statusCode,
        !empty($hostedCheckout['id_customer']) ? $hostedCheckout['id_customer'] : null,
        $dateAdd ?? null,
        $dateAdd ?? null,
        null,
        null,
        $captureTime
    ));

    return prepareDataForInsertOrUpdate($entity);
}

function mapTokensToEntityRow(array $token): array
{
    $entity = new TokenEntity();
    $entity->setStoreId((string)$token['id_shop']);
    $entity->setToken(new Token(
        (string)$token['id_customer'],
        (string)$token['value'],
        (string)$token['product_id'],
        (string)$token['card_number'],
        (string)$token['expiry_date']
    ));

    return prepareDataForInsertOrUpdate($entity);
}

function mapProductTypesToEntityRow(array $productType): array
{
    try {
        $type = ProductType::parse((string)$productType['product_type']);
    } catch (\Throwable $e) {
        $type = ProductType::foodAndDrink();
    }

    $entity = new ProductTypeEntity();
    $entity->setProductId((string)$productType['id_product']);
    $entity->setProductType($type);

    return prepareDataForInsertOrUpdate($entity);
}

function prepareDataForInsertOrUpdate(Entity $entity): array
{
    $indexes = IndexHelper::transformFieldsToIndexes($entity);
    $record = [
        'entity_type' => pSQL($entity->getConfig()->getType()),
        'data' => pSQL(json_encode($entity->toArray()), true)
    ];

    foreach ($indexes as $index => $value) {
        $record['index_' . $index] = $value !== null ? pSQL($value, true) : null;
    }

    return $record;
}
