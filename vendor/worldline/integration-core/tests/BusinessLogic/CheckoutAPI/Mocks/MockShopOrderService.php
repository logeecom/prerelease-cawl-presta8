<?php

namespace OnlinePayments\Core\Tests\BusinessLogic\CheckoutAPI\Mocks;

use OnlinePayments\Core\BusinessLogic\Domain\Integration\ShopOrderService;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentDetails;
use OnlinePayments\Core\BusinessLogic\Domain\Payment\PaymentTransaction;

/**
 * Class MockShopOrderService.
 *
 * @package CheckoutAPI\Mocks
 */
class MockShopOrderService implements ShopOrderService
{

    public function createShopOrder(PaymentTransaction $paymentTransaction, PaymentDetails $paymentDetails, string $newState): void
    {
    }

    public function updateStatus(PaymentTransaction $paymentTransaction, PaymentDetails $paymentDetails, string $newState): void
    {
    }

    public function cancelShopOrder(PaymentTransaction $paymentTransaction, PaymentDetails $paymentDetails, string $newState): void
    {
    }

    public function refundShopOrder(PaymentTransaction $paymentTransaction, PaymentDetails $paymentDetails, string $newState): void
    {
    }
}