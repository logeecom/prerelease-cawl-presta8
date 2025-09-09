<?php

namespace CAWL\OnlinePayments\Classes\Services\Integration;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService as BaseShopPaymentService;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
/**
 * Class ShopPaymentService
 *
 * @package OnlinePayments\Classes\Services\Integration
 * @internal
 */
class ShopPaymentService implements BaseShopPaymentService
{
    /**
     * @inheritDoc
     */
    public function savePaymentMethod(PaymentMethod $paymentMethod) : void
    {
    }
    /**
     * @inheritDoc
     */
    public function enable(string $paymentProductId, bool $enabled) : void
    {
    }
    /**
     * @inheritDoc
     */
    public function deleteAllPaymentMethods() : void
    {
    }
    public function deletePaymentMethods(string $mode) : void
    {
    }
}
