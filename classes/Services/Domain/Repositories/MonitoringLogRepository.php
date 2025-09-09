<?php

namespace CAWL\OnlinePayments\Classes\Services\Domain\Repositories;

use Context;
use CAWL\OnlinePayments\Core\Bootstrap\DataAccess\Monitoring\MonitoringLogRepository as CoreMonitoringLogRepository;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Monitoring\MonitoringLog;
use Order;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
/**
 * Class MonitoringLogRepository
 *
 * @package OnlinePayments\Classes\Services\Domain\Repositories
 */
class MonitoringLogRepository extends CoreMonitoringLogRepository
{
    /**
     * @param MonitoringLog $monitoringLog
     *
     * @return string
     */
    public function getOrderUrl(MonitoringLog $monitoringLog) : string
    {
        return $this->getOrderUrlByCartId($monitoringLog->getOrderId());
    }
    public function getOrderUrlByCartId(string $cartId) : string
    {
        $id = Order::getIdByCartId((int) $cartId);
        if (!SymfonyContainer::getInstance() || !$id) {
            return '';
        }
        return \rtrim(Context::getContext()->link->getBaseLink(), '/') . SymfonyContainer::getInstance()->get('router')->generate('admin_orders_view', ['orderId' => $id]);
    }
    /**
     * @param string $cartId
     *
     * @return string
     */
    public function getOrderIdByCartId(string $cartId) : string
    {
        return Order::getIdByCartId((int) $cartId);
    }
}
