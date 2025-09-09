<?php

namespace CAWL\OnlinePayments\Classes\Repositories;

/**
 * Class ConfigurationRepository
 *
 * @package OnlinePayments\Classes\Repositories
 * @internal
 */
class ConfigurationRepository
{
    public const TABLE_NAME = 'configuration';
    /**
     * @throws \PrestaShopDatabaseException
     */
    public function isStoreInMaintenanceMode(int $storeId) : bool
    {
        $maintenanceMode = \false;
        $query = 'SELECT *
                    FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`
                    WHERE `name` = "PS_SHOP_ENABLE"';
        $manuallyManagedStores = \Db::getInstance()->executeS($query);
        if (\count(\Shop::getShops()) === 1) {
            $filteredStores = \array_filter($manuallyManagedStores, static function ($store) {
                return $store['id_shop'] === null;
            });
            $defaultConfig = \reset($filteredStores);
            return !$defaultConfig['value'];
        }
        foreach ($manuallyManagedStores as $manualStore) {
            if ($manualStore['id_shop'] === (string) $storeId && $manualStore['value'] === null) {
                $maintenanceMode = \true;
            }
        }
        return $maintenanceMode;
    }
}
