<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod;

/**
 * Class PaymentProductService
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod
 */
class PaymentProductService
{
    public function getSupportedPaymentMethods() : array
    {
        return PaymentProductId::SUPPORTED_PAYMENT_PRODUCTS;
    }
}
