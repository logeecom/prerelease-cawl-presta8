<?php

namespace CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Repositories;

use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethod;
use CAWL\OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;
/**
 * Interface PaymentConfigRepositoryInterface
 *
 * @package OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\Repositories
 * @internal
 */
interface PaymentConfigRepositoryInterface
{
    /**
     * Retrieves all saved payment methods.
     *
     * @return PaymentMethodCollection
     */
    public function getPaymentMethods() : PaymentMethodCollection;
    public function getPaymentMethod(string $productId) : ?PaymentMethod;
    public function savePaymentMethod(PaymentMethod $paymentMethod) : void;
    /**
     * @param string $mode
     *
     * @return void
     */
    public function deleteByMode(string $mode) : void;
}
