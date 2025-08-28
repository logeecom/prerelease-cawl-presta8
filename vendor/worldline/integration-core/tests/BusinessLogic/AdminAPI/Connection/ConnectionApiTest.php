<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Connection;

use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use OnlinePayments\Core\Bootstrap\DataAccess\Connection\ConnectionConfigEntity;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\ConnectionAPI\Request\ConnectionRequest;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection\Proxies\ConnectionProxyInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionDetails;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Credentials;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\Infrastructure\ORM\RepositoryRegistry;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;
use OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Connection\Mocks\MockConnectionProxyInterface;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;

/**
 * Class ConnectionApiTest
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Connection
 */
class ConnectionApiTest extends BaseTestCase
{
    private string $storeId = 'test123';
    private MockConnectionProxyInterface $proxy;
    private ConnectionConfigRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $proxy = new MockConnectionProxyInterface();
        $this->proxy = $proxy;
        ServiceRegister::registerService(ConnectionProxyInterface::class, function () use ($proxy) {
            return $proxy;
        });
        $this->repository = ServiceRegister::getService(ConnectionConfigRepositoryInterface::class);
    }

    public function testConnect(): void
    {
        // arrange
        $request = new ConnectionRequest(
            'test',
            'asdfg',
            '1234567890',
            '1234567890asdfgh',
            'hjklbnm',
            'asdfgh1234567890'
        );
        $this->proxy->success = true;

        // act
        $response = AdminAPI::get()->connection($this->storeId)->connect($request);

        // assert
        self::assertTrue($response->isSuccessful(), print_r($response->toArray(), true));
        self::assertEquals(200, $response->getStatusCode());
        $savedEntity = StoreContext::doWithStore($this->storeId, function () {
            return $this->repository->getConnection();
        });
        self::assertNotNull($savedEntity);
        self::assertEquals((string)ConnectionMode::test(), $savedEntity->getMode());
    }

    public function testInvalidMode(): void
    {
        // arrange
        $request = new ConnectionRequest(
            'invalidMode',
            'asdfg',
            '1234567890',
            '1234567890asdfgh',
            'hjklbnm',
            'asdfgh1234567890'
        );
        $this->proxy->success = true;

        // act
        $response = AdminAPI::get()->connection($this->storeId)->connect($request);

        // assert
        self::assertFalse($response->isSuccessful());
        self::assertEquals('connection.invalidConnectionMode', $response->toArray()['errorCode']);
        $savedEntity = StoreContext::doWithStore($this->storeId, function () {
            return $this->repository->getConnection();
        });
        self::assertNull($savedEntity);
    }

    public function testEmptyTestCredentials(): void
    {
        // arrange
        $request = new ConnectionRequest(
            'test',
            '',
            '',
            '',
            '',
            ''
        );
        $this->proxy->success = true;

        // act
        $response = AdminAPI::get()->connection($this->storeId)->connect($request);

        // assert
        self::assertFalse($response->isSuccessful());
        $savedEntity = StoreContext::doWithStore($this->storeId, function () {
            return $this->repository->getConnection();
        });
        self::assertNull($savedEntity);
        $responseArray = $response->toArray();
        self::assertEquals('connection.invalidTestCredentials', $responseArray['errorCode']);
    }

    public function testEmptyLiveCredentials(): void
    {
        // arrange
        $request = new ConnectionRequest(
            'live',
            '',
            '',
            '',
            '',
            ''
        );
        $this->proxy->success = true;

        // act
        $response = AdminAPI::get()->connection($this->storeId)->connect($request);

        // assert
        self::assertFalse($response->isSuccessful());
        $savedEntity = StoreContext::doWithStore($this->storeId, function () {
            return $this->repository->getConnection();
        });
        self::assertNull($savedEntity);
        $responseArray = $response->toArray();
        self::assertEquals('connection.invalidLiveCredentials', $responseArray['errorCode']);
    }

    public function testConnectValidationFails(): void
    {
        // arrange
        $request = new ConnectionRequest(
            'test',
            'asdfg',
            '1234567890',
            '1234567890asdfgh',
            'hjklbnm',
            'asdfgh1234567890'
        );
        $this->proxy->success = false;

        // act
        $response = AdminAPI::get()->connection($this->storeId)->connect($request);

        // assert
        self::assertFalse($response->isSuccessful());
        self::assertEquals('connection.apiValidationFailed', $response->toArray()['errorCode']);
        $savedEntity = StoreContext::doWithStore($this->storeId, function () {
            return $this->repository->getConnection();
        });
        self::assertNull($savedEntity);
    }

    public function testConnectAlreadyConnected(): void
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
        $request = new ConnectionRequest(
            'test',
            'qwerty',
            '1234567890',
            '1234567890qwerty',
            'qwerty',
            'qwerty1234567890'
        );
        $this->proxy->success = true;

        // act
        $response = AdminAPI::get()->connection($this->storeId)->connect($request);

        // assert
        self::assertTrue($response->isSuccessful());
        $savedEntity = StoreContext::doWithStore($this->storeId, function () {
            return $this->repository->getConnection();
        });
        self::assertNotNull($savedEntity);
        self::assertEquals((string)ConnectionMode::test(), $savedEntity->getMode());
        self::assertEquals('qwerty', $savedEntity->getTestCredentials()->getPspid());
    }

    public function testSwitchEnvironment(): void
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
        $request = new ConnectionRequest(
            'live',
            'asdfg',
            '1234567890',
            '1234567890asdfgh',
            'hjklbnm',
            'asdfgh1234567890',
            'qwerty',
            '1234567890',
            '1234567890qwerty',
            'qwerty',
            'qwerty1234567890'
        );
        $this->proxy->success = true;

        // act
        $response = AdminAPI::get()->connection($this->storeId)->connect($request);

        // assert
        self::assertTrue($response->isSuccessful());
        $savedEntity = StoreContext::doWithStore($this->storeId, function () {
            return $this->repository->getConnection();
        });
        self::assertNotNull($savedEntity);
        self::assertEquals((string)ConnectionMode::live(), $savedEntity->getMode());
        self::assertEquals('asdfg', $savedEntity->getTestCredentials()->getPspid());
        self::assertEquals('qwerty', $savedEntity->getLiveCredentials()->getPspid());
    }

    public function testGetDetails(): void
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
        $response = AdminAPI::get()->connection($this->storeId)->getConnectionConfig();

        // assert
        self::assertTrue($response->isSuccessful());
        $responseArray = $response->toArray();
        self::assertEquals('asdfg', $responseArray['sandboxData']['pspid']);
        self::assertEquals('1234567890', $responseArray['sandboxData']['apiKey']);
        self::assertEquals('1234567890asdfgh', $responseArray['sandboxData']['apiSecret']);
        self::assertEquals('hjklbnm', $responseArray['sandboxData']['webhooksKey']);
        self::assertEquals('asdfgh1234567890', $responseArray['sandboxData']['webhooksSecret']);
    }
}
