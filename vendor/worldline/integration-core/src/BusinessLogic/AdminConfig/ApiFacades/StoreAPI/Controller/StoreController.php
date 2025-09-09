<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\StoreAPI\Controller;

use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\StoreAPI\Response\StoreOrderStatusesResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\StoreAPI\Response\StoreResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\StoreAPI\Response\StoresResponse;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Stores\Exceptions\FailedToRetrieveOrderStatusesException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Stores\Exceptions\FailedToRetrieveStoresException;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Stores\Models\Store;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Stores\StoreService;
/**
 * Class StoreController
 *
 * @package OnlinePayments\Core\BusinessLogic\AdminConfig\ApiFacades\StoreAPI\Controller
 * @internal
 */
class StoreController
{
    private StoreService $storeService;
    /**
     * @param StoreService $storeService
     */
    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
    }
    /**
     * @return StoresResponse
     *
     * @throws FailedToRetrieveStoresException
     */
    public function getStores() : StoresResponse
    {
        return new StoresResponse($this->storeService->getStores());
    }
    /**
     * @return StoreResponse
     *
     * @throws FailedToRetrieveStoresException
     */
    public function getCurrentStore() : StoreResponse
    {
        $currentStore = $this->storeService->getCurrentStore();
        return $currentStore ? new StoreResponse($currentStore) : new StoreResponse($this->failbackStore());
    }
    /**
     * @return StoreOrderStatusesResponse
     *
     * @throws FailedToRetrieveOrderStatusesException
     */
    public function getStoreOrderStatuses() : StoreOrderStatusesResponse
    {
        return new StoreOrderStatusesResponse($this->storeService->getStoreOrderStatuses());
    }
    private function failBackStore() : Store
    {
        return new Store('failBack', 'failBack', \false);
    }
}
