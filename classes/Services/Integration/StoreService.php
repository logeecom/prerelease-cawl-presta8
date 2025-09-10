<?php

namespace CAWL\OnlinePayments\Classes\Services\Integration;

use CAWL\OnlinePayments\Classes\OnlinePaymentsModule;
use CAWL\OnlinePayments\Classes\Repositories\ConfigurationRepository;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Stores\StoreService as StoreServiceInterface;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Multistore\StoreContext;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\OrderStatusMapping\Models\OrderStatusMapping;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Stores\Models\Store;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Stores\Models\StoreOrderStatus;
use OrderState;
use Shop;
/**
 * Class StoreService
 *
 * @package OnlinePayments\Classes\Services\Integration
 */
class StoreService implements StoreServiceInterface
{
    private ConfigurationRepository $configurationRepository;
    private OnlinePaymentsModule $module;
    /**
     * @param ConfigurationRepository $configurationRepository
     */
    public function __construct(ConfigurationRepository $configurationRepository, OnlinePaymentsModule $module)
    {
        $this->configurationRepository = $configurationRepository;
        $this->module = $module;
    }
    /**
     * @inheritDoc
     */
    public function getStoreDomain() : string
    {
        $storeId = StoreContext::getInstance()->getStoreId();
        $shop = Shop::getShop($storeId);
        if (\strpos($shop['domain'], '/') === \false) {
            return \Tools::getShopProtocol() . $shop['domain'];
        }
        return \Tools::getShopProtocol() . \substr($shop['domain'], 0, \strpos($shop['domain'], '/'));
    }
    /**
     * @inheritDoc
     */
    public function getStores() : array
    {
        $stores = [];
        foreach (Shop::getShops() as $shop) {
            $stores[] = new Store($shop['id_shop'], $shop['name'], $this->isStoreInMaintenanceMode($shop['id_shop']));
        }
        return $stores;
    }
    /**
     * @inheritDoc
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getDefaultStore() : ?Store
    {
        $defaultStore = null;
        $defaultStoreId = \Configuration::get('PS_SHOP_DEFAULT');
        foreach (Shop::getShops() as $shop) {
            if ($shop['id_shop'] === $defaultStoreId) {
                $defaultStore = new Store($shop['id_shop'], $shop['name'], $this->isStoreInMaintenanceMode($shop['id_shop']));
                break;
            }
        }
        return $defaultStore;
    }
    /**
     * @inheritDoc
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getStoreById(string $id) : ?Store
    {
        $shop = Shop::getShop($id);
        if (!$shop) {
            return null;
        }
        return new Store($shop['id_shop'], $shop['name'], $this->isStoreInMaintenanceMode($shop['id_shop']));
    }
    /**
     * Returns default status mapping.
     *
     * @return OrderStatusMapping
     */
    public function getDefaultOrderStatusMapping() : OrderStatusMapping
    {
        return new OrderStatusMapping((string) \Configuration::get('PS_OS_PAYMENT'), (string) \Configuration::get('PS_OS_ERROR'), (string) \Configuration::getGlobalValue($this->module->getBrand()->getCode() . '_PENDING_ORDER_STATUS_ID'), (string) \Configuration::getGlobalValue($this->module->getBrand()->getCode() . '_AWAITING_CAPTURE_STATUS_ID'), (string) \Configuration::getGlobalValue('PS_OS_CANCELED'), (string) \Configuration::get('PS_OS_REFUND'));
    }
    /**
     * @inheritDoc
     */
    public function getStoreOrderStatuses() : array
    {
        return $this->transformStoreOrderStatuses(OrderState::getOrderStates(\Context::getContext()->language->id));
    }
    /**
     * @throws \PrestaShopDatabaseException
     */
    private function isStoreInMaintenanceMode(int $storeId) : bool
    {
        return $this->configurationRepository->isStoreInMaintenanceMode($storeId);
    }
    /**
     * @param array $orderStates
     *
     * @return array
     */
    private function transformStoreOrderStatuses(array $orderStates) : array
    {
        return \array_filter(\array_map(function ($orderState) {
            if (empty($orderState['id_order_state']) || empty($orderState['name'])) {
                return null;
            }
            return new StoreOrderStatus($orderState['id_order_state'], $orderState['name']);
        }, $orderStates));
    }
}
