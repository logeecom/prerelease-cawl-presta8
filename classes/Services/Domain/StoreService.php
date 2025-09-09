<?php

namespace CAWL\OnlinePayments\Classes\Services\Domain;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Stores\Models\Store;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Stores\StoreService as BaseStoreService;
/**
 * Class StoreService
 *
 * @package OnlinePayments\Classes\Services\Domain
 */
class StoreService extends BaseStoreService
{
    /**
     * @inheritDoc
     */
    public function getCurrentStore() : ?Store
    {
        return $this->integrationStoreService->getStoreById((string) \Context::getContext()->shop->id);
    }
}
