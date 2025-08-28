<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\Sdk;

use OnlinePayments\Core\Bootstrap\DataAccess\Connection\ConnectionConfigEntity;
use OnlinePayments\Core\Bootstrap\Sdk\MerchantClientFactory;
use OnlinePayments\Core\Branding\Brand\ActiveBrandProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ActiveConnectionProvider;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidConnectionDetailsException;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;
use OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks\MockMetadataProvider;

/**
 * Class MerchantClientFactoryTest.
 *
 * @package OnlinePayments\Core\Tests\Bootstrap\Sdk
 */
class MerchantClientFactoryTest extends BaseTestCase
{
    public function testExceptionIsThrownInCaseOfNoActiveConnection(): void
    {
        self::expectException(InvalidConnectionDetailsException::class);

        /** @var MerchantClientFactory $factory */
        $factory = ServiceRegister::getService(MerchantClientFactory::class);

        $factory->get();
    }
    public function testExceptionIsThrownInCaseOfInvalidBrand(): void
    {
        self::expectException(\InvalidArgumentException::class);

        $factory = new MerchantClientFactory(
            ServiceRegister::getService(ActiveConnectionProvider::class),
            new ActiveBrandProvider(static function () {
                return 'InvalidBrand';
            }),
            new MockMetadataProvider()
        );
        $this->setUpTestConnectionInDb();

        StoreContext::doWithStore('132', static function () use ($factory) {
            return $factory->get();
        });
    }

    public function testClientFactoryCreatesMerchantClint(): void
    {
        /** @var MerchantClientFactory $factory */
        $factory = ServiceRegister::getService(MerchantClientFactory::class);
        $this->setUpTestConnectionInDb();

        $createdClient = StoreContext::doWithStore('132', static function () use ($factory) {
            return $factory->get();
        });

        self::assertTrue(is_callable([$createdClient, 'products']));
    }

    protected function setUpTestConnectionInDb(): void
    {
        $repository = RepositoryRegistry::getRepository(ConnectionConfigEntity::class);
        $repository->save(ConnectionConfigEntity::fromArray([
            'storeId' => '132',
            'connectionDetails' => [
                'mode' => (string)ConnectionMode::live(),
                'liveCredentials' => [
                    'pspId' => 'TESTPSP',
                    'apiKey' => 'TESTAPIKEY',
                    'apiSecret' => 'TESTAPISECRET',
                    'webhookKey' => 'TESTWEBHOOKKEY',
                    'webhookSecret' => 'TESTWEBHOOKSECRET',
                ]
            ]
        ]));
    }
}