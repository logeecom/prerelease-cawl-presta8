<?php

namespace OnlinePayments\Classes\Services\Domain\Repositories;

use Context;
use OnlinePayments\Core\Bootstrap\DataAccess\Monitoring\MonitoringLogRepository as CoreMonitoringLogRepository;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\MonitoringLog;
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
    public function getOrderUrl(MonitoringLog $monitoringLog): string
    {
        return $this->getOrderUrlByCartId($monitoringLog->getOrderId());
    }

    public function getOrderUrlByCartId(string $cartId): string
    {
        $id = Order::getIdByCartId((int)$cartId);

        if (!SymfonyContainer::getInstance() || !$id) {
            return '';
        }

        return rtrim(Context::getContext()->link->getBaseLink(), '/') . SymfonyContainer::getInstance()->get('router')
                ->generate('admin_orders_view', ['orderId' => $id]);
    }
}
