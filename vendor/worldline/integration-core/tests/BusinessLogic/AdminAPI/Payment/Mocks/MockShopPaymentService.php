<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Payment\Mocks;

use OnlinePayments\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;

/**
 * Class MockShopPaymentService
 *
 * @package OnlinePayments\Core\Tests\BusinessLogic\AdminAPI\Payment\Mocks
 */
class MockShopPaymentService implements ShopPaymentService
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

    public function deletePaymentMethods(string $mode): void
    {
    }
}
