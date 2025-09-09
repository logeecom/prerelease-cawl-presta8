<?php

namespace CAWL\OnlinePayments\Classes\Services;

/**
 * Class OrderStatusMapping
 *
 * @package OnlinePayments\Classes\Services
 * @internal
 */
class OrderStatusMappingService
{
    public const PRESTA_PAYMENT_ACCEPTED = 'Payment accepted';
    public const PRESTA_PAYMENT_ERROR = 'Payment error';
    public const PRESTA_AWAITING_PAYMENT_CONFIRMATION = 'Awaiting payment confirmation';
    public const PRESTA_REFUNDED = 'Refunded';
    public const PRESTA_PROCESSING = 'Processing in progress';
    public const PRESTA_CANCELED = 'Canceled';
    public const PRESTA_CANCELED_ID = '6';
    public const PRESTA_PAYMENT_ERROR_ID = '8';
    public const PRESTA_ON_BACKORDER_ID = '12';
    private static array $statusMap = [];
    /**
     * @param $status
     *
     * @return mixed
     */
    public static function getPrestaShopOrderStatusId($status)
    {
        return static::getStatusMap()[$status] ?? null;
    }
    /**
     * @return array
     */
    private static function getStatusMap() : array
    {
        if (!static::$statusMap) {
            static::$statusMap = \array_column(\OrderState::getOrderStates(1), 'id_order_state', 'name');
        }
        return static::$statusMap;
    }
}
