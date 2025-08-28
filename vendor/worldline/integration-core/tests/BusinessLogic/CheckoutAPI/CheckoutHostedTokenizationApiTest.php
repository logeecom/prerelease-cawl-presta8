<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI;

use OnlinePayments\Core\Bootstrap\ApiFacades\PaymentProcessor\CheckoutAPI\CheckoutAPI;
use OnlinePayments\Core\Bootstrap\DataAccess\Connection\ConnectionConfigEntity;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use OnlinePayments\Core\BusinessLogic\Domain\HostedTokenization\HostedTokenization;
use OnlinePayments\Core\BusinessLogic\PaymentProcessor\Proxies\HostedTokenizationProxyInterface;
use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;
use OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks\MockCartProvider;
use OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks\MockHostedTokenizationProxy;

/**
 * Class CheckoutHostedTokenizationApiTest.
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI
 */
class CheckoutHostedTokenizationApiTest extends BaseTestCase
{
    private string $storeId = 'test123';
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTestConnectionInDb();
    }

    public function testCreateHostedTokenization(): void
    {
        $this->setupHostedTokenizationApiResponse(new HostedTokenization('https://test.hosted.tokenization.url', []));

        $response = CheckoutAPI::get()->hostedTokenization($this->storeId)->crate(new MockCartProvider());

        self::assertTrue($response->isSuccessful(), print_r($response->toArray(), true));
        self::assertSame('https://test.hosted.tokenization.url', $response->getHostedTokenization()->getUrl());
    }

    protected function setUpTestConnectionInDb(): void
    {
        $repository = RepositoryRegistry::getRepository(ConnectionConfigEntity::class);
        $repository->save(ConnectionConfigEntity::fromArray([
            'storeId' => $this->storeId,
            'connectionDetails' => [
                'mode' => (string)ConnectionMode::test(),
                'testCredentials' => [
                    'pspId' => 'TESTPSP',
                    'apiKey' => 'TESTAPIKEY',
                    'apiSecret' => 'TESTAPISECRET',
                    'webhookKey' => 'TESTWEBHOOKKEY',
                    'webhookSecret' => 'TESTWEBHOOKSECRET',
                ]
            ]
        ]));
    }

    protected function setupHostedTokenizationApiResponse(HostedTokenization $hostedTokenization = null): void
    {
        $proxy = new MockHostedTokenizationProxy($hostedTokenization);
        ServiceRegister::registerService(HostedTokenizationProxyInterface::class, static function () use ($proxy) {
            return $proxy;
        });
    }
}