<?php

namespace OnlinePayments\Core\BusinessLogic\PaymentProcessor\Repositories;

use OnlinePayments\Core\BusinessLogic\Domain\PaymentMethod\PaymentMethodCollection;

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
    public function getEnabled(): PaymentMethodCollection;
}