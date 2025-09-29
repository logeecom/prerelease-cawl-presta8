<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\PaymentProcessor\Repositories;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
/**
 * Interface PaymentMethodConfigRepositoryInterface.
 *
 * @package OnlinePayments\Core\BusinessLogic\PaymentProcessor\Repositories
 */
interface PaymentMethodConfigRepositoryInterface
{
    /**
     * @return PaymentMethodCollection
     */
    public function getEnabled() : PaymentMethodCollection;
    public function getPaymentMethod(string $productId) : ?PaymentMethod;
}
