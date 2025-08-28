<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Integration;

use Exception;
use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\IntegrationAPI\Response\StateResponse;
use OnlinePayments\Core\BusinessLogic\AdminConfig\Services\Connection\Proxies\ConnectionProxyInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionDetails;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\ConnectionMode;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Credentials;
use OnlinePayments\Core\BusinessLogic\Domain\Connection\Repositories\ConnectionConfigRepositoryInterface;
use OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;
use OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Connection\Mocks\MockConnectionProxyInterface;

/**
 * Class IntegrationApiTest
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Integration
 */
class IntegrationApiTest extends BaseTestCase
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

    public function testStateWithoutCredentials(): void
    {
        // act
        $result = AdminAPI::get()->integration($this->storeId)->getState();

        // assert
        self::assertEquals(StateResponse::connection(), $result);
    }

    /**
     * @throws Exception
     */
    public function testStateWithCredentials(): void
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
        $this->proxy->success = true;

        // act
        $result = AdminAPI::get()->integration($this->storeId)->getState();

        // assert
        self::assertEquals(StateResponse::payments(), $result);
    }

    /**
     * @throws Exception
     */
    public function testWithWrongCredentials(): void
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
        $this->proxy->success = false;

        // act
        $result = AdminAPI::get()->integration($this->storeId)->getState();

        // assert
        self::assertEquals(StateResponse::connection(), $result);
    }

    /**
     * @throws Exception
     */
    public function testWithMultipleCredentials(): void
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
        $connectionDetails1 = new ConnectionDetails(
            ConnectionMode::test(),
            null,
            new Credentials(
                'qwerty',
                '0987654321',
                '0987654321qwerty',
                'qwerty',
                '0987654321qwerty'
            )
        );
        StoreContext::doWithStore('test', function () use ($connectionDetails1) {
            $this->repository->saveConnection($connectionDetails1);
        });
        $this->proxy->success = true;

        // act
        $result = AdminAPI::get()->integration('test')->getState();

        // assert
        self::assertEquals(StateResponse::payments(), $result);
    }

    /**
     * @throws Exception
     */
    public function testWithMultipleWrongCredentials(): void
    {
        $connectionDetails = new ConnectionDetails(
            ConnectionMode::test(),
            null,
            new Credentials(
                '',
                '',
                '1234567890asdfgh',
                'hjklbnm',
                'asdfgh1234567890'
            )
        );
        StoreContext::doWithStore($this->storeId, function () use ($connectionDetails) {
            $this->repository->saveConnection($connectionDetails);
        });
        $connectionDetails1 = new ConnectionDetails(
            ConnectionMode::test(),
            null,
            new Credentials(
                '',
                '',
                '0987654321qwerty',
                'qwerty',
                '0987654321qwerty'
            )
        );
        StoreContext::doWithStore('test', function () use ($connectionDetails1) {
            $this->repository->saveConnection($connectionDetails1);
        });
        $this->proxy->success = false;

        // act
        $result = AdminAPI::get()->integration('test')->getState();

        // assert
        self::assertEquals(StateResponse::connection(), $result);
    }
}
