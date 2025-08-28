<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\StoreAPI;

use OnlinePayments\Core\Bootstrap\ApiFacades\AdminConfig\AdminAPI\AdminAPI;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\StoreAPI\Response\StoreResponse;
use OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\StoreAPI\Response\StoresResponse;
use OnlinePayments\Core\BusinessLogic\Domain\Integration\Stores\StoreService;
use OnlinePayments\Core\BusinessLogic\Domain\Stores\Models\Store;
use OnlinePayments\Core\Infrastructure\ServiceRegister;
use OnlinePayments\Core\Tests\Bootstrap\BaseTestCase;
use OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\StoreAPI\Mocks\MockIntegrationStoreService;

/**
 * Class StoreApiTest
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\StoreAPI
 */
class StoreApiTest extends BaseTestCase
{
    /**
     * @var MockIntegrationStoreService
     */
    private $integrationStoreService;

    public function setUp(): void
    {
        parent::setUp();

        $storeService = new MockIntegrationStoreService();
        $this->integrationStoreService = $storeService;
        ServiceRegister::registerService(
            StoreService::class,
            function () use ($storeService) {
                return $storeService;
            }
        );
    }

    /**
     * @return void
     */
    public function testIsStoresResponseSuccessful(): void
    {
        // act
        $stores = AdminAPI::get()->store('1')->getStores();

        // assert
        self::assertTrue($stores->isSuccessful());
    }

    /**
     * @return void
     */
    public function testIsStoreResponseSuccessful(): void
    {
        // arrange

        // act
        $currentStore = AdminAPI::get()->store('1')->getCurrentStore();

        // assert
        self::assertTrue($currentStore->isSuccessful());
    }

    /**
     * @return void
     */
    public function testDefaultStoreResponse(): void
    {
        // arrange
        $this->integrationStoreService->setMockDefaultStore(new Store('store1', 'store12', true));

        // act
        $currentStore = AdminAPI::get()->store('1')->getCurrentStore();

        // assert
        self::assertEquals($currentStore, $this->expectedDefaultStoreResponse());
    }

    /**
     * @return void
     */
    public function testDefaultStoreResponseToArray(): void
    {
        // arrange
        $this->integrationStoreService->setMockDefaultStore(new Store('store1', 'store12', true));

        // act
        $currentStore = AdminAPI::get()->store('1')->getCurrentStore();

        // assert
        self::assertEquals($currentStore->toArray(), $this->expectedDefaultStoreResponse()->toArray());
    }

    /**
     * @return void
     */
    public function testFailBackStoreResponse(): void
    {
        // arrange
        $this->integrationStoreService->setMockStoreByIdStore(null);
        $this->integrationStoreService->setMockDefaultStore(null);

        // act
        $currentStore = AdminAPI::get()->store('1')->getCurrentStore();

        // assert
        self::assertEquals($currentStore, $this->expectedFailBackResponse());
    }

    /**
     * @return void
     */
    public function testStoresResponse(): void
    {
        // arrange
        $this->integrationStoreService->setMockStores(
            [
                new Store('store1', 'store1', true),
                new Store('store2', 'store2', false),
                new Store('store3', 'store3', true)
            ]
        );

        // act
        $stores = AdminAPI::get()->store('1')->getStores();

        // assert
        self::assertEquals($stores, $this->expectedStoresResponse());
    }

    /**
     * @return void
     */
    public function testStoresResponseToArray(): void
    {
        // arrange
        $this->integrationStoreService->setMockStores(
            [
                new Store('store1', 'store1', true),
                new Store('store2', 'store2', false),
                new Store('store3', 'store3', true)
            ]
        );

        // act
        $stores = AdminAPI::get()->store('1')->getStores();

        // assert
        self::assertEquals($stores->toArray(), $this->expectedStoresResponse()->toArray());
    }

    /**
     * @return StoresResponse
     */
    private function expectedStoresResponse(): StoresResponse
    {
        return new StoresResponse([
            new Store('store1', 'store1', true),
            new Store('store2', 'store2', false),
            new Store('store3', 'store3', true)
        ]);
    }


    /**
     * @return StoreResponse
     */
    private function expectedDefaultStoreResponse(): StoreResponse
    {
        return new StoreResponse(new Store('store1', 'store12', true));
    }

    /**
     * @return StoreResponse
     */
    private function expectedStoreByIdResponse(): StoreResponse
    {
        return new StoreResponse(new Store('1', 'store1', true));
    }

    /**
     * @return StoreResponse
     */
    private function expectedFailBackResponse(): StoreResponse
    {
        return new StoreResponse(new Store('failBack', 'failBack', false));
    }
}
