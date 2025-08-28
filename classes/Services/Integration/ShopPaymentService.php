<?php

namespace OnlinePayments\Classes\Services\Integration;

use OnlinePayments\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService as BaseShopPaymentService;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;

/**
 * Class ShopPaymentService
 *
 * @package OnlinePayments\Classes\Services\Integration
 */
class ShopPaymentService implements BaseShopPaymentService
{

    /**
     * @inheritDoc
     */
    public function savePaymentMethod(PaymentMethod $paymentMethod): void
    {
    }

    /**
     * @inheritDoc
     */
    public function enable(string $paymentProductId, bool $enabled): void
    {
    }

    /**
     * @inheritDoc
     */
    public function deleteAllPaymentMethods(): void
    {
    }

    public function deletePaymentMethods(string $mode): void
    {
    }
}
