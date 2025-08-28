<?php

namespace OnlinePayments\Classes\Services\Domain\Repositories;

use Context;
use OnlinePayments\Core\Bootstrap\DataAccess\Monitoring\WebhookLogRepository as CoreWebhookLogRepository;
use OnlinePayments\Core\BusinessLogic\Domain\Monitoring\WebhookLog;
use Order;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

/**
 * Class WebhookLogRepository
 *
 * @package OnlinePayments\Classes\Services\Domain\Repositories
 */
class WebhookLogRepository extends CoreWebhookLogRepository
{
    /**
     * @param WebhookLog $webhookLog
     *
     * @return string
     */
    public function getOrderUrl(WebhookLog $webhookLog): string
    {
        return $this->getOrderUrlByCartId($webhookLog->getOrderId());
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
