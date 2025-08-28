<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Payment;

use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\PaymentAPI\Request\PaymentMethodRequest;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionDetails;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Credentials;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Logo\LogoUrlService;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentProductId;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Repositories\PaymentConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\Translation;
use OnlinePayments\Core\BusinessLogic\Domain\Translations\Model\TranslationCollection;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;
use OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Payment\Mocks\MockLogoUrlService;
use OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Payment\Mocks\MockShopPaymentService;

/**
 * Class PaymentApiTest
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Payment
 */
class PaymentApiTest extends BaseTestCase
{
    private string $storeId = 'test123';
    private ConnectionConfigRepositoryInterface $repository;
    private PaymentConfigRepositoryInterface $paymentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = ServiceRegister::getService(ConnectionConfigRepositoryInterface::class);
        ServiceRegister::registerService(ShopPaymentService::class, function () {
            return new MockShopPaymentService();
        });
        ServiceRegister::registerService(LogoUrlService::class, function () {
            return new MockLogoUrlService();
        });
        $this->paymentRepository = ServiceRegister::getService(PaymentConfigRepositoryInterface::class);
    }

    public function testListNoConfiguredMethods(): void
    {
        // arrange
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

        // act
        $result = AdminAPI::get()->payment($this->storeId)->list();

        // assert
        self::assertEquals(true, $result->isSuccessful());
        self::assertCount(44, $result->toArray());
    }

    public function testListConfiguredMethods(): void
    {
        // arrange
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
        StoreContext::doWithStore($this->storeId, function () {
            $this->paymentRepository->savePaymentMethod(new PaymentMethod(
                PaymentProductId::cards(),
                new TranslationCollection(new Translation('EN', 'Credit Cards')),
                true,
                ''
            ));
        });

        // act
        $result = AdminAPI::get()->payment($this->storeId)->list();

        // assert
        self::assertEquals(true, $result->isSuccessful());
        self::assertCount(44, $result->toArray());
    }

    public function testSavePaymentMethod(): void
    {
        // arrange
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

        // act
        AdminAPI::get()->payment($this->storeId)->save(new PaymentMethodRequest(
            'cards',
            ['EN' => 'Credit Cards'],
            true,
            '',
            ['EN' => 'Vault title']
        ));

        // assert
        $saved = StoreContext::doWithStore($this->storeId, function () {
            return $this->paymentRepository->getPaymentMethod('cards');
        });
        self::assertNotEmpty($saved);
    }

    public function testEnable(): void
    {
        // arrange
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

        // act
        AdminAPI::get()->payment($this->storeId)->enable('cards', true);

        // assert
        $saved = StoreContext::doWithStore($this->storeId, function () {
            return $this->paymentRepository->getPaymentMethod('cards');
        });
        self::assertNotEmpty($saved);
    }

    public function testEnableExistingConfig(): void
    {
        // arrange
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
        StoreContext::doWithStore($this->storeId, function () {
            $this->paymentRepository->savePaymentMethod(new PaymentMethod(
                PaymentProductId::cards(),
                new TranslationCollection(new Translation('EN', 'Credit Cards')),
                false,
                ''
            ));
        });

        // act
        AdminAPI::get()->payment($this->storeId)->enable('cards', true);

        // assert
        $saved = StoreContext::doWithStore($this->storeId, function () {
            return $this->paymentRepository->getPaymentMethod('cards');
        });
        self::assertNotEmpty($saved);
        self::assertTrue($saved->isEnabled());
    }

    public function testDisable(): void
    {
        // arrange
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
        StoreContext::doWithStore($this->storeId, function () {
            $this->paymentRepository->savePaymentMethod(new PaymentMethod(
                PaymentProductId::cards(),
                new TranslationCollection(new Translation('EN', 'Credit Cards')),
                true,
                ''
            ));
        });

        // act
        AdminAPI::get()->payment($this->storeId)->enable('cards', false);

        // assert
        $saved = StoreContext::doWithStore($this->storeId, function () {
            return $this->paymentRepository->getPaymentMethod('cards');
        });
        self::assertNotEmpty($saved);
        self::assertFalse($saved->isEnabled());
    }

    public function testGetPaymentMethod()
    {
        // arrange
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
        StoreContext::doWithStore($this->storeId, function () {
            $this->paymentRepository->savePaymentMethod(new PaymentMethod(
                PaymentProductId::cards(),
                new TranslationCollection(new Translation('EN', 'Credit Cards')),
                true,
                ''
            ));
        });

        // act
        $result = AdminAPI::get()->payment($this->storeId)->getPaymentMethod('cards');

        // assert
        self::assertTrue($result->isSuccessful());
        self::assertEquals([
            'paymentProductId' => 'cards',
            'name' => [
                0 => [
                    'locale' => 'EN',
                    'value' => 'Credit Cards'
                ]
            ],
            'enabled' => true,
            'template' => '',
            'additionalData' => [],
        ], $result->toArray());
    }
}
