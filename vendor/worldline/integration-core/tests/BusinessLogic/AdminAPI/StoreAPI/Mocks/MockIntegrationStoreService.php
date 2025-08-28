<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\StoreAPI\Mocks;

use OnlinePayments\Core\BusinessLogic\Domain\Integration\Stores\StoreService;
use OnlinePayments\Core\BusinessLogic\Domain\OrderStatusMapping\Models\OrderStatusMapping;
use OnlinePayments\Core\BusinessLogic\Domain\Stores\Models\Store;

class MockIntegrationStoreService implements StoreService
{
    /**
     * @var Store
     */
    private $defaultStore;

    /**
     * @var Store
     */
    private $storeById;

    /**
     * @var Store[]
     */
    private $stores;

    public function __construct()
    {
        $this->stores = [];
        $this->defaultStore = new Store('1', 'name1', true);
        $this->storeById = new Store('1', 'name1', true);
    }

    /**
     * @inheritDoc
     */
    public function getStoreDomain(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getStores(): array
    {
        return $this->stores;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultStore(): ?Store
    {
        return $this->defaultStore;
    }

    /**
     * @inheritDoc
     */
    public function getStoreById(string $id): ?Store
    {
        return $this->storeById;
    }
    /**
     * @param Store|null $store
     *
     * @return void
     */
    public function setMockDefaultStore(?Store $store): void
    {
        $this->defaultStore = $store;
    }

    /**
     * @param Store|null $store
     *
     * @return void
     */
    public function setMockStoreByIdStore(?Store $store): void
    {
        $this->storeById = $store;
    }

    /**
     * @param Store[] $stores
     *
     * @return void
     */
    public function setMockStores(array $stores): void
    {
        $this->stores = $stores;
    }

    public function getStoreOrderStatuses(): array
    {
        return [];
    }

    public function getDefaultOrderStatusMapping(): OrderStatusMapping
    {
        return new OrderStatusMapping(
            'captured',
            'error',
            'pending',
            'authorized',
            'cancelled',
            'refunded',
        );
    }
}
