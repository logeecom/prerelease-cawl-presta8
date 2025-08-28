<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\GeneralSettings;

use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\CardsSettingsRequest;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\LogSettingsRequest;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\PayByLinkSettingsRequest;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\GeneralSettingsAPI\Request\PaymentSettingsRequest;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\CardsSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\LogSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\PayByLinkSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\GeneralSettings\Repositories\PaymentSettingsRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Amount;
use OnlinePayments\Core\BusinessLogic\Domain\Checkout\Currency;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionDetails;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Credentials;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\AutomaticCapture;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\CardsSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\ExemptionType;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\LogRecordsLifetime;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\LogSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PayByLinkExpirationTime;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PayByLinkSettings;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAction;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentAttemptsNumber;
use OnlinePayments\Core\BusinessLogic\Domain\GeneralSettings\PaymentSettings;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Stores\StoreService;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;
use OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\StoreAPI\Mocks\MockIntegrationStoreService;

/**
 * Class GeneralSettingsApiTest
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\GeneralSettings
 */
class GeneralSettingsApiTest extends BaseTestCase
{
    private string $storeId = 'test123';
    private ConnectionConfigRepositoryInterface $repository;
    private CardsSettingsRepositoryInterface $cardsSettingsRepository;
    private PaymentSettingsRepositoryInterface $paymentSettingsRepository;
    private LogSettingsRepositoryInterface $logSettingsRepository;
    private PayByLinkSettingsRepositoryInterface $payByLinkSettingsRepository;

    protected function setUp(): void
    {
        parent::setUp();

        ServiceRegister::registerService(
            StoreService::class,
            function () {
                return new MockIntegrationStoreService();
            }
        );
        $this->repository = ServiceRegister::getService(ConnectionConfigRepositoryInterface::class);
        $this->cardsSettingsRepository = ServiceRegister::getService(CardsSettingsRepositoryInterface::class);
        $this->paymentSettingsRepository = ServiceRegister::getService(PaymentSettingsRepositoryInterface::class);
        $this->logSettingsRepository = ServiceRegister::getService(LogSettingsRepositoryInterface::class);
        $this->payByLinkSettingsRepository = ServiceRegister::getService(PayByLinkSettingsRepositoryInterface::class);

        $connectionDetails = new ConnectionDetails(
            ConnectionMode::test(),
            null,
            new Credentials(
                'asdfg',
                '1234567890',
                '1234567890asdfgh',
                'hjklbnm',
                'asdfgh1234567890'
            )
        );
        StoreContext::doWithStore($this->storeId, function () use ($connectionDetails) {
            $this->repository->saveConnection($connectionDetails);
        });
    }

    public function testGetGeneralSettings(): void
    {
        // arrange
        StoreContext::doWithStore($this->storeId, function () {
            $this->cardsSettingsRepository->saveCardsSettings(new CardsSettings(
                false,
                true,
                true,
                ExemptionType::lowValue(),
                Amount::fromInt(3000, Currency::fromIsoCode('EUR'))
            ));
        });
        StoreContext::doWithStore($this->storeId, function () {
            $this->logSettingsRepository->saveLogSettings(new LogSettings(true, LogRecordsLifetime::create(10)));
        });

        // act
        $result = AdminAPI::get()->generalSettings($this->storeId)->getGeneralSettings();

        // assert
        $expected = $this->getGeneralSettingsDefaultValues();
        $expected['logSettings'] = [
            'debugMode' => true,
            'logDays' => 10,
        ];
        $expected['cardsSettings'] = [
            'enable3ds' => false,
            'enforceStrongAuthentication' => true,
            'enable3dsExemption' => true,
            'exemptionType' => 'low-value',
            'exemptionLimit' => 30,
        ];
        self::assertEquals($expected, $result->toArray());
    }

    public function testGetGeneralSettingsDefaultValues(): void
    {
        // act
        $result = AdminAPI::get()->generalSettings($this->storeId)->getGeneralSettings();

        // assert
        self::assertEquals($this->getGeneralSettingsDefaultValues(), $result->toArray());
    }

    public function testSaveCardsSettings(): void
    {
        // act
        $result = AdminAPI::get()->generalSettings($this->storeId)->saveCardsSettings(
            new CardsSettingsRequest(
                true,
                true,
                true,
                'transaction-risk-analysis',
                13.23
            )
        );

        // assert
        self::assertTrue($result->isSuccessful());
        $savedEntity = StoreContext::doWithStore($this->storeId, function () {
            return $this->cardsSettingsRepository->getCardsSettings();
        });
        $expected = new CardsSettings(
            true,
            true,
            true,
            ExemptionType::transactionRiskAnalysis(),
            Amount::fromFloat(13.23, Currency::fromIsoCode('EUR'))
        );
        self::assertEquals($expected, $savedEntity);
    }

    public function testSavePaymentSettings(): void
    {
        // act
        $result = AdminAPI::get()->generalSettings($this->storeId)->savePaymentSettings(
            new PaymentSettingsRequest(
                'FINAL_AUTHORIZATION',
                60,
                3,
                true,
                'captured',
                'error',
                'pending',
                'authorized',
                'cancelled',
                'refunded',
            )
        );

        // assert
        self::assertTrue($result->isSuccessful());
        $savedEntity = StoreContext::doWithStore($this->storeId, function () {
            return $this->paymentSettingsRepository->getPaymentSettings();
        });
        $expected = new PaymentSettings(
            PaymentAction::authorize(),
            AutomaticCapture::create(60),
            PaymentAttemptsNumber::create(3),
            true,
            'captured',
            'error',
            'pending',
            'authorized',
            'cancelled',
            'refunded',
        );
        self::assertEquals($expected, $savedEntity);
    }

    public function testSaveLogSettings(): void
    {
        // act
        $result = AdminAPI::get()->generalSettings($this->storeId)->saveLogSettings(
            new LogSettingsRequest(
                false,
                4
            )
        );

        // assert
        self::assertTrue($result->isSuccessful());
        self::assertEmpty($result->toArray());
        $savedEntity = StoreContext::doWithStore($this->storeId, function () {
            return $this->logSettingsRepository->getLogSettings();
        });
        $expected = new LogSettings(
            false,
            LogRecordsLifetime::create(4)
        );
        self::assertEquals($expected, $savedEntity);
    }

    public function testSavePayByLinkSettings(): void
    {
        // act
        $result = AdminAPI::get()->generalSettings($this->storeId)->savePayByLinkSettings(
            new PayByLinkSettingsRequest(
                true,
                'Test',
                2
            )
        );

        // assert
        self::assertTrue($result->isSuccessful());
        self::assertEmpty($result->toArray());
        $savedEntity = StoreContext::doWithStore($this->storeId, function () {
            return $this->payByLinkSettingsRepository->getPayByLinkSettings();
        });
        $expected = new PayByLinkSettings(
            true,
            'Test',
            PayByLinkExpirationTime::create(2)
        );
        self::assertEquals($expected, $savedEntity);
    }

    public function testDisconnect(): void
    {
        // arrange
        StoreContext::doWithStore($this->storeId, function () {
            $this->cardsSettingsRepository->saveCardsSettings(new CardsSettings(
                false,
                true,
                true,
                ExemptionType::lowValue(),
                Amount::fromInt(3000, Currency::fromIsoCode('EUR'))
            ));
        });
        StoreContext::doWithStore($this->storeId, function () {
            $this->logSettingsRepository->saveLogSettings(new LogSettings(true, LogRecordsLifetime::create(10)));
        });

        // act
        $result = AdminAPI::get()->generalSettings($this->storeId)->disconnect();

        // assert
        self::assertTrue($result->isSuccessful());
        self::assertEmpty($result->toArray());
        $connection = StoreContext::doWithStore($this->storeId, function () {
            return $this->repository->getConnection();
        });
        $cardsSettings = StoreContext::doWithStore($this->storeId, function () {
            return $this->cardsSettingsRepository->getCardsSettings();
        });
        $logSettings = StoreContext::doWithStore($this->storeId, function () {
            return $this->logSettingsRepository->getLogSettings();
        });
        self::assertNull($connection);
        self::assertNull($cardsSettings);
        self::assertNull($logSettings);
    }

    private function getGeneralSettingsDefaultValues(): array
    {
        return [
            'accountSettings' => [
                'mode' => 'test',
                'sandboxData' => [
                    'pspid' => 'asdfg',
                    'apiKey' => '1234567890',
                    'apiSecret' => '1234567890asdfgh',
                    'webhooksKey' => 'hjklbnm',
                    'webhooksSecret' => 'asdfgh1234567890',
                ],
                'liveData' => [
                    'pspid' => '',
                    'apiKey' => '',
                    'apiSecret' => '',
                    'webhooksKey' => null,
                    'webhooksSecret' => null,
                ],
            ],
            'paymentSettings' => [
                'paymentAction' => 'SALE',
                'automaticCapture' => -1,
                'numberOfPaymentAttempts' => 10,
                'applySurcharge' => false,
                'paymentCapturedStatus' => 'captured',
                'paymentErrorStatus' => 'error',
                'paymentPendingStatus' => 'pending',
                'paymentAuthorizedStatus' => 'authorized',
                'paymentCancelledStatus' => 'cancelled',
                'paymentRefundedStatus' => 'refunded'
            ],
            'cardsSettings' => [
                'enable3ds' => true,
                'enforceStrongAuthentication' => false,
                'enable3dsExemption' => false,
                'exemptionType' => 'low-value',
                'exemptionLimit' => 30,
            ],
            'logSettings' => [
                'debugMode' => false,
                'logDays' => 14,
            ],
            'payByLinkSettings' => [
                'enabled' => false,
                'title' => 'Worldline Pay by Link',
                'expirationTime' => 7
            ]
        ];
    }
}
